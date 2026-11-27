<?php

namespace App\Services;

use App\Models\User;

/**
 * Monthly interview allowance during the validation phase (MVP item 9).
 *
 * An interview is consumed when it starts (started_at), not when it is
 * configured, so abandoned drafts never use up the allowance. This will be
 * backed by usage_records in step 4.
 */
class InterviewUsage
{
    public function limit(): int
    {
        return (int) config('faladev.usage.monthly_session_limit');
    }

    public function usedThisMonth(User $user): int
    {
        return $user->interviewSessions()
            ->where('started_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function remaining(User $user): int
    {
        return max($this->limit() - $this->usedThisMonth($user), 0);
    }

    /**
     * @return array{used: int, limit: int, remaining: int}
     */
    public function summary(User $user): array
    {
        $used = $this->usedThisMonth($user);

        return [
            'used' => $used,
            'limit' => $this->limit(),
            'remaining' => max($this->limit() - $used, 0),
        ];
    }
}
