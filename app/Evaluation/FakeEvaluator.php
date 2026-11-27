<?php

namespace App\Evaluation;

use App\Enums\InterviewCategory;
use App\Evaluation\Contracts\Evaluator;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use Illuminate\Support\Str;

/**
 * Development stand-in until the LLM evaluator exists. It does NOT judge the
 * answers: every score is a fixed 3 and every text says it is simulated, so
 * the report can never be mistaken for real feedback. It only quotes the
 * user's own answers, to exercise the evidence layout end to end.
 */
class FakeEvaluator implements Evaluator
{
    private const SIMULATED_SCORE = 3;

    /** @var array<string, list<string>> */
    private const NEXT_STEPS = [
        InterviewCategory::Algorithms->value => [
            'Explique em voz alta, em inglês, a complexidade de tempo e de espaço de cada solução que praticar.',
            'Antes de resolver, liste os casos de borda (entrada vazia, um elemento, valores repetidos).',
        ],
        InterviewCategory::SystemDesign->value => [
            'Comece cada resposta perguntando pelos requisitos funcionais e não funcionais.',
            'Para cada componente que propuser, diga um trade-off em uma frase.',
        ],
        InterviewCategory::Behavioral->value => [
            'Prepare dois exemplos reais com contexto, a sua ação e o resultado medido.',
            'Treine contar cada exemplo em até dois minutos, em inglês.',
        ],
    ];

    public function name(): string
    {
        return 'fake';
    }

    public function evaluate(InterviewSession $session): EvaluationResult
    {
        $answered = $session->turns()->whereNotNull('answered_at')->get();
        /** @var InterviewTurn $first */
        $first = $answered->first();
        /** @var InterviewTurn $last */
        $last = $answered->last();

        return new EvaluationResult(
            overallScore: self::SIMULATED_SCORE,
            summary: 'Relatório simulado para testar o fluxo: as notas não avaliam suas respostas. O avaliador real ainda não está ligado.',
            dimensions: array_map(fn (array $dimension) => [
                'key' => $dimension['key'],
                'label' => $dimension['label'],
                'score' => self::SIMULATED_SCORE,
                'explanation' => 'Nota simulada. O avaliador real vai explicar a nota com base nas suas respostas.',
            ], Rubric::dimensions($session->category)),
            strengths: [[
                'point' => 'Exemplo de ponto forte (simulado).',
                'evidence' => $this->excerpt($first->answer_transcript),
                'position' => $first->position,
            ]],
            improvements: [[
                'point' => 'Exemplo de oportunidade de melhoria (simulado).',
                'evidence' => $this->excerpt($last->answer_transcript),
                'position' => $last->position,
                'action' => 'Com o avaliador real, aqui aparece uma ação prática para a próxima entrevista.',
            ]],
            englishFeedback: [[
                'original' => $this->firstSentence($first->answer_transcript),
                'suggestion' => $this->firstSentence($first->answer_transcript),
                'explanation' => 'Simulado: nenhuma correção foi feita.',
                'position' => $first->position,
            ]],
            nextSteps: self::NEXT_STEPS[$session->category->value],
            metadata: ['answered_turns' => $answered->count()],
        );
    }

    private function excerpt(?string $text): string
    {
        return Str::limit((string) $text, 160);
    }

    private function firstSentence(?string $text): string
    {
        $sentence = preg_split('/(?<=[.!?])\s+/', trim((string) $text))[0] ?? '';

        return Str::limit($sentence, 160);
    }
}
