<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const RECENT_SESSIONS = 5;

    /**
     * Home screen: start a new interview, see recent ones and remaining usage.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $recentSessions = $user->interviewSessions()
            ->latest()
            ->latest('id')
            ->limit(self::RECENT_SESSIONS)
            ->get()
            ->map(fn (InterviewSession $session) => [
                'id' => $session->id,
                'category' => $session->category->label(),
                'level' => $session->level->label(),
                'duration' => $session->duration_minutes->label(),
                'status' => $session->status->value,
                'statusLabel' => $session->status->label(),
                'createdAt' => $session->created_at?->toIso8601String(),
            ]);

        // Until usage_records exists (step 4), a session counts once it is created.
        $usedThisMonth = $user->interviewSessions()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return Inertia::render('Dashboard', [
            'recentSessions' => $recentSessions,
            'usage' => [
                'used' => $usedThisMonth,
                'limit' => config('faladev.usage.monthly_session_limit'),
            ],
        ]);
    }
}
