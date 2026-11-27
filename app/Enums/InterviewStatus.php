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
