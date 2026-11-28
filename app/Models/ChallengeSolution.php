<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's latest passing code for a code challenge.
 *
 * @property int $id
 * @property int $user_id
 * @property string $challenge_slug
 * @property string $code
 * @property CarbonImmutable $first_solved_at
 * @property CarbonImmutable $last_solved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['challenge_slug', 'code', 'first_solved_at', 'last_solved_at'])]
class ChallengeSolution extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_solved_at' => 'immutable_datetime',
            'last_solved_at' => 'immutable_datetime',
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
