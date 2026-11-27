<?php

namespace App\Enums;

/**
 * Only the durations the MVP can honour consistently (15 or 30 minutes).
 */
enum InterviewDuration: int
{
    case Short = 15;
    case Long = 30;

    /**
     * Label shown in the (pt-BR) interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Short => '15 minutos',
            self::Long => '30 minutos',
        };
    }

    /**
     * Short explanation shown on the interview setup screen.
     */
    public function description(): string
    {
        return match ($this) {
            self::Short => 'Sessão curta, boa para praticar com frequência.',
            self::Long => 'Sessão completa, mais próxima de uma entrevista real.',
        };
    }
}
