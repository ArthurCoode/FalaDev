<?php

namespace App\Enums;

enum InterviewCategory: string
{
    case Algorithms = 'algorithms';
    case SystemDesign = 'system_design';
    case Behavioral = 'behavioral';

    /**
     * Label shown in the (pt-BR) interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Algorithms => 'Algoritmos',
            self::SystemDesign => 'System design',
            self::Behavioral => 'Comportamental',
        };
    }
}
