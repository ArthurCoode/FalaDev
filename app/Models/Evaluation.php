<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The report of one interview (cursor/skills/FalaDev.md, section 7).
 * The JSON shapes are documented in App\Evaluation\EvaluationResult.
 *
 * @property int $id
 * @property int $interview_session_id
 * @property string $evaluator
 * @property int $overall_score
 * @property string $summary
 * @property list<array{key: string, label: string, score: int, explanation: string}> $dimensions
 * @property list<array{point: string, evidence: string, position: int}> $strengths
 * @property list<array{point: string, evidence: string, position: int, action: string}> $improvements
 * @property list<array{original: string, suggestion: string, explanation: string, position: int}> $english_feedback
 * @property list<string> $next_steps
 * @property bool|null $user_found_useful
 * @property string|null $user_comment
 * @property CarbonImmutable|null $user_rated_at
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['evaluator', 'overall_score', 'summary', 'dimensions', 'strengths', 'improvements', 'english_feedback', 'next_steps', 'metadata', 'user_found_useful', 'user_comment', 'user_rated_at'])]
class Evaluation extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'overall_score' => 'integer',
            'dimensions' => 'array',
            'strengths' => 'array',
            'improvements' => 'array',
            'english_feedback' => 'array',
            'next_steps' => 'array',
            'metadata' => 'array',
            'user_found_useful' => 'boolean',
            'user_rated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InterviewSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }
}
