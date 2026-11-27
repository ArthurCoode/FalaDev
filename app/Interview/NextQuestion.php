<?php

namespace App\Interview;

final readonly class NextQuestion
{
    public function __construct(
        public string $text,
        public bool $isFollowUp = false,
    ) {}
}
