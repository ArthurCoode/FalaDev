<?php

namespace App\Enums;

/**
 * Why an allowance unit was consumed (FalaDev.md, section 10).
 */
enum UsageReason: string
{
    case InterviewStarted = 'interview_started';
}
