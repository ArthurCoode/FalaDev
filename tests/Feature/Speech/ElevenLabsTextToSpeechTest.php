<?php

use App\Models\InterviewSession;
use App\Speech\Contracts\TextToSpeech;
use App\Speech\ElevenLabsTextToSpeech;
use App\Speech\Exceptions\SpeechSynthesisFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function elevenLabs(): ElevenLabsTextToSpeech
{
    return new ElevenLabsTextToSpeech(
        apiKey: 'secret-key',
        voiceId: 'voice-123',
        modelId: 'eleven_v4_turbo',
    );
}

test('it sends the text to the configured voice and model', function () {
    Http::fake(['api.elevenlabs.io/*' => Http::response('mp3-bytes', 200, ['Content-Type' => 'audio/mpeg'])]);

    $speech = elevenLabs()->synthesize('Tell me about yourself.');

    expect($speech->audio)->toBe('mp3-bytes')
        ->and($speech->mimeType)->toBe('audio/mpeg');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.elevenlabs.io/v1/text-to-speech/voice-123?output_format=mp3_44100_64'
        && $request->hasHeader('xi-api-key', 'secret-key')
        && $request['text'] === 'Tell me about yourself.'
        && $request['model_id'] === 'eleven_v4_turbo');
});

test('a provider error becomes SpeechSynthesisFailed without leaking the key', function () {
    Http::fake(['api.elevenlabs.io/*' => Http::response(['detail' => 'quota_exceeded'], 401, ['request-id' => 'req-1'])]);

    try {
        elevenLabs()->synthesize('Hello');
        $this->fail('Expected SpeechSynthesisFailed.');
    } catch (SpeechSynthesisFailed $e) {
        expect($e->getMessage())->toContain('HTTP 401')
            ->toContain('req-1')
            ->not->toContain('secret-key');
    }
});

test('a network failure becomes SpeechSynthesisFailed', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    elevenLabs()->synthesize('Hello');
})->throws(SpeechSynthesisFailed::class, 'ElevenLabs could not be reached.');

test('it refuses to start without an API key', function () {
    new ElevenLabsTextToSpeech(apiKey: '', voiceId: 'v', modelId: 'm');
})->throws(InvalidArgumentException::class);

test('the container builds the ElevenLabs driver from config', function () {
    config(['faladev.speech.tts' => 'elevenlabs']);

    expect(app(TextToSpeech::class))->toBeInstanceOf(ElevenLabsTextToSpeech::class);
});

test('the sample phrase is synthesized once and then served from cache', function () {
    config(['faladev.speech.tts' => 'elevenlabs']);
    Http::fake(['api.elevenlabs.io/*' => Http::response('mp3-bytes', 200, ['Content-Type' => 'audio/mpeg'])]);
    $session = InterviewSession::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $this->actingAs($session->user)
            ->get(route('interviews.sample-audio', $session))
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg');
    }

    Http::assertSentCount(1);
});

test('a failed synthesis is not cached, so a retry calls the provider again', function () {
    config(['faladev.speech.tts' => 'elevenlabs']);
    Http::fakeSequence('api.elevenlabs.io/*')
        ->push('error', 500)
        ->push('mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']);
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)->get(route('interviews.sample-audio', $session))->assertStatus(503);
    $this->actingAs($session->user)->get(route('interviews.sample-audio', $session))->assertOk();

    Http::assertSentCount(2);
});
