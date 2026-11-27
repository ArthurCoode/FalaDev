<?php

namespace App\Evaluation;

use InvalidArgumentException;

/**
 * What an evaluator returns. Validated here so a malformed report (for
 * example, from an LLM) is rejected instead of shown as final (section 5C).
 */
final readonly class EvaluationResult
{
    /**
     * @param  list<array{key: string, label: string, score: int, explanation: string}>  $dimensions
     * @param  list<array{point: string, evidence: string, position: int}>  $strengths  Evidence is quoted from the answer at that question position.
     * @param  list<array{point: string, evidence: string, position: int, action: string}>  $improvements
     * @param  list<array{original: string, suggestion: string, explanation: string, position: int}>  $englishFeedback
     * @param  list<string>  $nextSteps
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $overallScore,
        public string $summary,
        public array $dimensions,
        public array $strengths,
        public array $improvements,
        public array $englishFeedback,
        public array $nextSteps,
        public array $metadata = [],
    ) {
        foreach ([$overallScore, ...array_column($dimensions, 'score')] as $score) {
            if ($score < Rubric::MIN_SCORE || $score > Rubric::MAX_SCORE) {
                throw new InvalidArgumentException("Score out of range: {$score}.");
            }
        }

        if ($dimensions === [] || trim($summary) === '') {
            throw new InvalidArgumentException('An evaluation needs a summary and dimensions.');
        }
    }
}
