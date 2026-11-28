<?php

namespace App\Models;

use App\Enums\StudySessionKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A finished lesson or review.
 *
 * @property int $id
 * @property int $user_id
 * @property string $attempt_id
 * @property StudySessionKind $kind
 * @property string|null $lesson_slug
 * @property int $correct
 * @property int $total
 * @property int $xp
 * @property CarbonImmutable $study_on
 * @property array<string, mixed> $outcome
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['attempt_id', 'kind', 'lesson_slug', 'correct', 'total', 'xp', 'study_on', 'outcome'])]
class StudySession extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => StudySessionKind::class,
            'correct' => 'integer',
            'total' => 'integer',
            'xp' => 'integer',
            'study_on' => 'immutable_date',
            'outcome' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
