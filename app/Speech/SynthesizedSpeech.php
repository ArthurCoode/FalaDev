<?php

namespace App\Speech;

final readonly class SynthesizedSpeech
{
    public function __construct(
        public string $audio,
        public string $mimeType,
    ) {}
}
