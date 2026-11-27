<?php

namespace App\Enums;

/**
 * Lifecycle of an interview session (see cursor/skills/FalaDev.md, section 8).
 * Transition rules are not enforced yet; they arrive with the turn-based flow (step 2).
 */
enum InterviewStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Asking = 'asking';
    case Recording = 'recording';
    case Transcribing = 'transcribing';
    case ReviewingAnswer = 'reviewing_answer';
    case ProcessingAnswer = 'processing_answer';
    case Completed = 'completed';
    case Evaluating = 'evaluating';
    case ReportReady = 'report_ready';
    case FailedRetryable = 'failed_retryable';
}
