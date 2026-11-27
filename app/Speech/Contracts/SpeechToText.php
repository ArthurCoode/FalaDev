<?php

namespace App\Speech\Contracts;

use App\Speech\Exceptions\TranscriptionFailed;

/**
 * Turns one recorded answer into text. Runs only on the server. The audio
 * file is temporary: implementations must not keep it after transcribing
 * (cursor/skills/FalaDev.md, section 9).
 */
interface SpeechToText
{
    /**
     * @param  string  $path  Local path of the uploaded audio.
     *
     * @throws TranscriptionFailed
     */
    public function transcribe(string $path, string $mimeType): string;
}
