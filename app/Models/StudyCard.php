<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An exercise in the user's review queue (App\Study\ReviewSchedule).
 *
 * @property int $id
 * @property int $user_id
 * @property string $exercise_key
 * @property int $box
 * @property CarbonImmutable|null $due_on
 * @property int $lapses
 * @property CarbonImmutable|null $last_reviewed_at
 * @property CarbonImmutable|null $mastered_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['exercise_key'])]
class StudyCard extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'box' => 0,
        'lapses' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'box' => 'integer',
            'lapses' => 'integer',
            'last_reviewed_at' => 'immutable_datetime',
            'mastered_at' => 'immutable_datetime',
        ];
    }

    /**
     * A calendar date, stored as Y-m-d (never with a time, so comparing it
     * with another date works the same on every database).
     *
     * @return Attribute<CarbonImmutable|null, CarbonInterface|null>
     */
    protected function dueOn(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : CarbonImmutable::parse(substr($value, 0, 10)),
            set: fn (?CarbonInterface $value) => $value?->toDateString(),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cards waiting for review on the given study day, oldest first.
     *
     * @param  Builder<StudyCard>  $query
     */
    public function scopeDueOn(Builder $query, CarbonImmutable $today): void
    {
        $query->whereNull('mastered_at')
            ->where('due_on', '<=', $today->toDateString())
            ->orderBy('due_on')
            ->orderBy('id');
    }
}
