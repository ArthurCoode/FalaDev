<?php

namespace App\Models;

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewLevel;
use App\Enums\InterviewStatus;
use Database\Factories\InterviewSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property InterviewCategory $category
 * @property InterviewLevel $level
 * @property InterviewDuration $duration_minutes
 * @property InterviewStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['category', 'level', 'duration_minutes'])]
class InterviewSession extends Model
{
    /** @use HasFactory<InterviewSessionFactory> */
    use HasFactory;

    /**
     * Status is not mass assignable on purpose: it must only change through
     * explicit transitions, never straight from request input.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => InterviewStatus::Draft->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => InterviewCategory::class,
            'level' => InterviewLevel::class,
            'duration_minutes' => InterviewDuration::class,
            'status' => InterviewStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<InterviewTurn, $this>
     */
    public function turns(): HasMany
    {
        return $this->hasMany(InterviewTurn::class)->orderBy('position');
    }
}
