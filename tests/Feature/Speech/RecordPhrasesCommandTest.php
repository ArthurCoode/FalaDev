<?php

use App\Speech\Contracts\TextToSpeech;
use App\Speech\SynthesizedSpeech;

function fakeMp3TextToSpeech(): object
{
    $tts = new class implements TextToSpeech
    {
        /** @var list<string> */
        public array $spoken = [];

        public function synthesize(string $text): SynthesizedSpeech
        {
            $this->spoken[] = $text;

            return new SynthesizedSpeech('mp3:'.$text, 'audio/mpeg');
        }
    };
    app()->instance(TextToSpeech::class, $tts);

    return $tts;
}

test('it records every fixed interviewer line', function () {
    $phrases = fakePrerecordedPhrases();
    $tts = fakeMp3TextToSpeech();

    $this->artisan('faladev:record-phrases')->assertSuccessful();

    expect($tts->spoken)->toContain(config('faladev.speech.sample_phrase'))
        ->toContain(config('faladev.interview.opening'))
        ->toContain(config('faladev.interview.closing'))
        ->and($phrases->has(config('faladev.interview.opening')))->toBeTrue();
});

test('lines that already have a recording are not sent again', function () {
    fakePrerecordedPhrases();
    fakeMp3TextToSpeech();
    $this->artisan('faladev:record-phrases')->assertSuccessful();

    $tts = fakeMp3TextToSpeech();
    $this->artisan('faladev:record-phrases')->assertSuccessful();

    expect($tts->spoken)->toBe([]);
});

test('a dry run does not call the provider', function () {
    fakePrerecordedPhrases();
    $tts = fakeMp3TextToSpeech();

    $this->artisan('faladev:record-phrases --dry-run')->assertSuccessful();

    expect($tts->spoken)->toBe([]);
});

test('it refuses a provider that does not produce MP3, such as the fake chime', function () {
    $phrases = fakePrerecordedPhrases();

    $this->artisan('faladev:record-phrases')->assertFailed();

    expect($phrases->has(config('faladev.speech.sample_phrase')))->toBeFalse();
});

test('editing a line points to a new file name', function () {
    $phrases = fakePrerecordedPhrases();

    expect($phrases->fileName('Hello.'))->not->toBe($phrases->fileName('Hello!'))
        ->and($phrases->url('Hello.'))->toBeNull();
});
