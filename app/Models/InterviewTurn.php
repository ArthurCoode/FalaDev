<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\InterviewTurnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $interview_session_id
 * @property int $position
 * @property string $question
 * @property bool $is_follow_up
 * @property string|null $answer_transcript
 * @property CarbonImmutable|null $answered_at
 * @property bool $hint_used
 * @property int $repeat_count
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['position', 'question', 'is_follow_up'])]
class InterviewTurn extends Model
{
    /** @use HasFactory<InterviewTurnFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_follow_up' => 'boolean',
            'answered_at' => 'datetime',
            'hint_used' => 'boolean',
            'repeat_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<InterviewSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }
}
