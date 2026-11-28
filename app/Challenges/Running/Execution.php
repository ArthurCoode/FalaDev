<?php

namespace App\Challenges\Running;

/**
 * What a run of a program printed and how it ended.
 */
final readonly class Execution
{
    public function __construct(
        public ?int $exitCode,
        public string $stdout,
        public string $stderr,
        public float $seconds,
        public bool $timedOut = false,
        public bool $outputLimitHit = false,
        /** The runner itself failed (no Docker, missing image). */
        public bool $unavailable = false,
    ) {}

    public static function unavailable(string $reason): self
    {
        return new self(null, '', $reason, 0.0, unavailable: true);
    }
}
