<?php

namespace App\Enums;

/**
 * Only the durations the MVP can honour consistently (15 or 30 minutes).
 */
enum InterviewDuration: int
{
    case Short = 15;
    case Long = 30;
}
