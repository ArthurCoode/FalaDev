<?php

namespace App\Enums;

enum InterviewLevel: string
{
    case Junior = 'junior';
    case Mid = 'mid';
    case Senior = 'senior';

    /**
     * Label shown in the (pt-BR) interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Junior => 'Júnior',
            self::Mid => 'Pleno',
            self::Senior => 'Sênior',
        };
    }
}
