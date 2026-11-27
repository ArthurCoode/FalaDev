<?php

namespace App\Services;

use App\Enums\UsageReason;
use App\Models\InterviewSession;
use App\Models\User;

/**
 * Monthly interview allowance during the validation phase (MVP item 9).
 *
 * An interview is consumed when it starts, not when it is configured, so
 * abandoned drafts never use up the allowance. Consumption is stored in
 * usage_records, which outlive the sessions: deleting an interview does not
 * give the allowance back.
 */
class InterviewUsage
{
    public function limit(): int
    {
        return (int) config('faladev.usage.monthly_session_limit');
    }

    public function usedThisMonth(User $user): int
    {
        return $user->usageRecords()
            ->where('reason', UsageReason::InterviewStarted)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function remaining(User $user): int
    {
        return max($this->limit() - $this->usedThisMonth($user), 0);
    }

    public function recordInterviewStarted(InterviewSession $session): void
    {
        $session->user->usageRecords()->create([
            'interview_session_id' => $session->id,
            'reason' => UsageReason::InterviewStarted,
        ]);
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
