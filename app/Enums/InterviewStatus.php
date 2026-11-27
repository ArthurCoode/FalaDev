<?php

namespace App\Enums;

/**
 * Lifecycle of an interview session (see cursor/skills/FalaDev.md, section 8).
 * Allowed transitions are added here as each part of the flow is built.
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

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            // Setup confirmed after the microphone and audio checks.
            self::Draft => [self::Ready],
            // Changing the setup sends the session back to the checks.
            self::Ready => [self::Draft],
            default => [],
        }, true);
    }

    /**
     * Label shown in the (pt-BR) interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Em configuração',
            self::Ready => 'Pronta para começar',
            self::Asking,
            self::Recording,
            self::Transcribing,
            self::ReviewingAnswer,
            self::ProcessingAnswer => 'Em andamento',
            self::Completed => 'Concluída',
            self::Evaluating => 'Preparando feedback',
            self::ReportReady => 'Relatório pronto',
            self::FailedRetryable => 'Avaliação indisponível',
        };
    }
}
