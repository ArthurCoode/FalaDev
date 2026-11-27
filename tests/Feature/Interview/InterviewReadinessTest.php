<?php

use App\Enums\InterviewCategory;
use App\Enums\InterviewStatus;
use App\Models\InterviewSession;
use App\Models\User;
use App\Speech\Contracts\TextToSpeech;
use App\Speech\Exceptions\SpeechSynthesisFailed;
use App\Speech\SynthesizedSpeech;
use Inertia\Testing\AssertableInertia as Assert;

test('the session page includes the sample phrase for the audio check', function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('samplePhrase', config('faladev.speech.sample_phrase')));
});

test('the sample audio is served by the TTS adapter', function () {
    $session = InterviewSession::factory()->create();

    $response = $this->actingAs($session->user)
        ->get(route('interviews.sample-audio', $session))
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/wav');

    expect(substr($response->getContent(), 0, 4))->toBe('RIFF');
});

test('a TTS failure returns 503 so the page can offer the text and a retry', function () {
    app()->bind(TextToSpeech::class, fn () => new class implements TextToSpeech
    {
        public function synthesize(string $text): SynthesizedSpeech
        {
            throw new SpeechSynthesisFailed('provider down');
        }
    });
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->get(route('interviews.sample-audio', $session))
        ->assertStatus(503)
        ->assertJson(['message' => 'Não foi possível gerar o áudio agora.']);
});

test("the sample audio of another user's session is not available", function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('interviews.sample-audio', $session))
        ->assertNotFound();
});

test('finishing the checks marks a draft session as ready', function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->post(route('interviews.ready', $session))
        ->assertRedirect(route('interviews.show', $session));

    expect($session->fresh()->status)->toBe(InterviewStatus::Ready);
});

test('marking ready twice is harmless', function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)->post(route('interviews.ready', $session));
    $this->actingAs($session->user)
        ->post(route('interviews.ready', $session))
        ->assertSessionHasNoErrors();

    expect($session->fresh()->status)->toBe(InterviewStatus::Ready);
});

test('a finished session cannot go back to ready', function () {
    $session = InterviewSession::factory()->completed()->create();

    $this->actingAs($session->user)
        ->post(route('interviews.ready', $session))
        ->assertSessionHasErrors('status');

    expect($session->fresh()->status)->toBe(InterviewStatus::ReportReady);
});

test("users cannot mark another user's session as ready", function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('interviews.ready', $session))
        ->assertNotFound();

    expect($session->fresh()->status)->toBe(InterviewStatus::Draft);
});

test('changing the setup of a ready session sends it back to the checks', function () {
    $user = User::factory()->create();
    $session = InterviewSession::factory()->for($user)->create();
    $this->actingAs($user)->post(route('interviews.ready', $session));

    $this->actingAs($user)->post(route('interviews.store'), [
        'category' => 'behavioral',
        'level' => 'mid',
        'duration_minutes' => '15',
        'data_notice_accepted' => 'on',
    ])->assertRedirect(route('interviews.show', $session));

    $session->refresh();
    expect($user->interviewSessions()->count())->toBe(1)
        ->and($session->status)->toBe(InterviewStatus::Draft)
        ->and($session->category)->toBe(InterviewCategory::Behavioral);
});
