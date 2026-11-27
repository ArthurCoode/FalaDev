<?php

use App\Speech\FakeTextToSpeech;

test('the fake TTS returns a well-formed WAV file', function () {
    $speech = (new FakeTextToSpeech)->synthesize('Hello');
    $audio = $speech->audio;

    $riffSize = unpack('V', substr($audio, 4, 4))[1];
    $dataSize = unpack('V', substr($audio, 40, 4))[1];
    $sampleRate = unpack('V', substr($audio, 24, 4))[1];

    expect($speech->mimeType)->toBe('audio/wav')
        ->and(substr($audio, 0, 4))->toBe('RIFF')
        ->and(substr($audio, 8, 4))->toBe('WAVE')
        ->and($riffSize)->toBe(strlen($audio) - 8)
        ->and($dataSize)->toBe(strlen($audio) - 44)
        ->and($sampleRate)->toBe(16000);
});
