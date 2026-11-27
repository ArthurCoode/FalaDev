<?php

namespace App\Speech\Contracts;

use App\Speech\Exceptions\SpeechSynthesisFailed;
use App\Speech\SynthesizedSpeech;

/**
 * Turns interviewer text into audio. Implementations wrap a provider and run
 * only on the server, so provider credentials never reach the browser.
 */
interface TextToSpeech
{
    /**
     * @throws SpeechSynthesisFailed
     */
    public function synthesize(string $text): SynthesizedSpeech;
}
