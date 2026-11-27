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

    /**
     * Short explanation shown on the interview setup screen.
     */
    public function description(): string
    {
        return match ($this) {
            self::Algorithms => 'Resolver um problema explicando a abordagem, os casos de borda e a complexidade.',
            self::SystemDesign => 'Desenhar um sistema discutindo requisitos, componentes, armazenamento e trade-offs.',
            self::Behavioral => 'Contar situações reais de trabalho: conflitos, falhas, colaboração e impacto.',
        };
    }
}
