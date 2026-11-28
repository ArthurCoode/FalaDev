<?php

namespace App\Challenges\Running;

use App\Enums\ChallengeLanguage;

/**
 * A challenge's test program ready to run: its files and the marker its
 * result lines start with.
 */
final readonly class Program
{
    /**
     * @param  array<string, string>  $files
     */
    public function __construct(
        public string $slug,
        public ChallengeLanguage $language,
        public array $files,
        public string $marker,
    ) {}
}
