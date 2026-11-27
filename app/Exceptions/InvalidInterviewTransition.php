<?php

namespace App\Exceptions;

use App\Enums\InterviewStatus;
use RuntimeException;

class InvalidInterviewTransition extends RuntimeException
{
    public function __construct(
        public readonly InterviewStatus $from,
        public readonly InterviewStatus $to,
    ) {
        parent::__construct("Interview session cannot go from {$from->value} to {$to->value}.");
    }
}
