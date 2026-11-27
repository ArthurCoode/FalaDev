<?php

namespace App\Models;

use App\Enums\PilotEventType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One technical event for the pilot metrics. Never audio or answer text.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $interview_session_id
 * @property PilotEventType $type
 * @property array<string, mixed>|null $data
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['user_id', 'interview_session_id', 'type', 'data'])]
class PilotEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PilotEventType::class,
            'data' => 'array',
        ];
    }

    /**
     * Records an event; failures here must never break the user's flow.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function record(PilotEventType $type, int $userId, ?int $sessionId = null, ?array $data = null): void
    {
        try {
            self::create([
                'user_id' => $userId,
                'interview_session_id' => $sessionId,
                'type' => $type,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
