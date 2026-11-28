<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's streak and XP total. Study days are dates in
 * config('faladev.study.timezone').
 *
 * Streak rules:
 * - studying on consecutive days adds one day;
 * - a missed day is covered by a freeze, if the user has one; otherwise the
 *   streak starts over;
 * - a freeze is earned every `freeze_every_days` days in a row, up to
 *   `max_freezes`.
 *
 * @property int $id
 * @property int $user_id
 * @property int $current_streak
 * @property int $longest_streak
 * @property CarbonImmutable|null $last_study_on
 * @property int $freezes
 * @property int $total_xp
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class StudyProgress extends Model
{
    protected $table = 'study_progress';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'current_streak' => 0,
        'longest_streak' => 0,
        'freezes' => 0,
        'total_xp' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'freezes' => 'integer',
            'total_xp' => 'integer',
        ];
    }

    /**
     * A calendar date, stored as Y-m-d (never with a time, so comparing it
     * with another date works the same on every database).
     *
     * @return Attribute<CarbonImmutable|null, CarbonInterface|null>
     */
    protected function lastStudyOn(): Attribute
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
     * Counts a study day. Studying twice on the same day changes nothing.
     *
     * @return array{extended: bool, freezesUsed: int, freezeEarned: bool}
     */
    public function recordStudyDay(CarbonImmutable $today): array
    {
        $result = ['extended' => false, 'freezesUsed' => 0, 'freezeEarned' => false];
        $missed = $this->missedDaysBefore($today);

        if ($missed === null) {
            $this->current_streak = 1;
        } elseif ($missed < 0) {
            // Already studied today (or the clock went back): nothing to add.
            return $result;
        } elseif ($missed <= $this->freezes) {
            $this->freezes -= $missed;
            $this->current_streak += 1;
            $result['freezesUsed'] = $missed;
        } else {
            $this->current_streak = 1;
        }

        $result['extended'] = true;
        $this->last_study_on = $today;
        $this->longest_streak = max($this->longest_streak, $this->current_streak);

        $every = (int) config('faladev.study.freeze_every_days');

        if ($this->current_streak % $every === 0 && $this->freezes < (int) config('faladev.study.max_freezes')) {
            $this->freezes += 1;
            $result['freezeEarned'] = true;
        }

        return $result;
    }

    /**
     * The streak as it stands today, without writing anything: it is still
     * alive while the freezes can cover the days missed since the last study.
     */
    public function streakOn(CarbonImmutable $today): int
    {
        $missed = $this->missedDaysBefore($today);

        if ($missed === null) {
            return 0;
        }

        return $missed <= $this->freezes ? $this->current_streak : 0;
    }

    public function studiedOn(CarbonImmutable $today): bool
    {
        return $this->last_study_on?->toDateString() === $today->toDateString();
    }

    /**
     * Days without study between the last study day and today: 0 when the
     * last one was yesterday, -1 when it was today, null when never.
     */
    private function missedDaysBefore(CarbonImmutable $today): ?int
    {
        if ($this->last_study_on === null) {
            return null;
        }

        // Compare calendar dates, not instants: the stored date and "today"
        // may carry different time zones.
        $last = CarbonImmutable::parse($this->last_study_on->toDateString(), 'UTC');
        $now = CarbonImmutable::parse($today->toDateString(), 'UTC');

        return (int) $last->diffInDays($now, false) - 1;
    }
}
