<?php

namespace App\Evaluation;

use App\Enums\InterviewCategory;

/**
 * The scoring criteria shared by every evaluator, fake or real
 * (cursor/skills/FalaDev.md, section 7). The technical dimension changes
 * with the category; the others are the same for all interviews.
 */
final class Rubric
{
    public const MIN_SCORE = 1;

    public const MAX_SCORE = 5;

    /**
     * Anchors shown next to every score, so the scale is always explained.
     *
     * @return array<int, string>
     */
    public static function anchors(): array
    {
        return [
            1 => 'insuficiente',
            3 => 'adequado, com lacunas',
            5 => 'forte para o nível selecionado',
        ];
    }

    /**
     * @return list<array{key: string, label: string, description: string}>
     */
    public static function dimensions(InterviewCategory $category): array
    {
        return [
            [
                'key' => 'technical',
                ...match ($category) {
                    InterviewCategory::Algorithms => [
                        'label' => 'Conteúdo técnico',
                        'description' => 'Correção da abordagem, casos de borda e análise de complexidade.',
                    ],
                    InterviewCategory::SystemDesign => [
                        'label' => 'Conteúdo técnico',
                        'description' => 'Requisitos, trade-offs e coerência da arquitetura.',
                    ],
                    InterviewCategory::Behavioral => [
                        'label' => 'Conteúdo do exemplo',
                        'description' => 'Relevância do exemplo, clareza da sua ação e do resultado.',
                    ],
                },
            ],
            [
                'key' => 'reasoning',
                'label' => 'Estrutura do raciocínio',
                'description' => 'Capacidade de organizar a resposta e explicar as decisões.',
            ],
            [
                'key' => 'communication',
                'label' => 'Comunicação e clareza',
                'description' => 'Respostas objetivas, fáceis de acompanhar e completas.',
            ],
            [
                'key' => 'english',
                'label' => 'Inglês',
                'description' => 'Gramática, vocabulário e clareza do texto transcrito. Não avalia pronúncia nem sotaque.',
            ],
        ];
    }
}
