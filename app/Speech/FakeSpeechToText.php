<?php

namespace App\Speech;

use App\Speech\Contracts\SpeechToText;

/**
 * Development stand-in until an STT provider is chosen. It ignores the audio
 * and returns a placeholder the user replaces with their answer while
 * reviewing, which exercises the whole record → review → send flow.
 */
class FakeSpeechToText implements SpeechToText
{
    public const TRANSCRIPT = '[Simulated transcript] Speech-to-text is not connected yet. Replace this text with what you said before sending your answer.';

    public function transcribe(string $path, string $mimeType): string
    {
        return self::TRANSCRIPT;
    }
}
