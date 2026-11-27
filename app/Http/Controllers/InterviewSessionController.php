<?php

namespace App\Http\Controllers;

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewLevel;
use App\Enums\InterviewStatus;
use App\Http\Requests\Interview\StoreInterviewSessionRequest;
use App\Models\InterviewSession;
use App\Services\InterviewUsage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InterviewSessionController extends Controller
{
    /**
     * Interview setup: category, level, duration and the data notice.
     */
    public function create(Request $request, InterviewUsage $usage): Response
    {
        $draft = $this->currentDraft($request);

        return Inertia::render('interviews/Create', [
            'options' => [
                'categories' => array_map(fn (InterviewCategory $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'description' => $case->description(),
                ], InterviewCategory::cases()),
                'levels' => array_map(fn (InterviewLevel $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], InterviewLevel::cases()),
                'durations' => array_map(fn (InterviewDuration $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'description' => $case->description(),
                ], InterviewDuration::cases()),
            ],
            'draft' => $draft ? [
                'category' => $draft->category->value,
                'level' => $draft->level->value,
                'duration_minutes' => $draft->duration_minutes->value,
            ] : null,
            'usage' => $usage->summary($request->user()),
        ]);
    }

    /**
     * Save the setup as the user's single draft session.
     */
    public function store(StoreInterviewSessionRequest $request, InterviewUsage $usage): RedirectResponse
    {
        if ($usage->remaining($request->user()) === 0) {
            throw ValidationException::withMessages([
                'usage' => 'Você atingiu o limite de entrevistas deste mês.',
            ]);
        }

        // Reusing the open draft makes repeated submits (double click, back and
        // resubmit) update one session instead of creating duplicates.
        $session = $this->currentDraft($request) ?? new InterviewSession;
        $session->fill($request->safe()->only(['category', 'level', 'duration_minutes']));
        $request->user()->interviewSessions()->save($session);

        return to_route('interviews.show', $session);
    }

    /**
     * Session overview. Other users' sessions respond 404, not 403, so their
     * existence is not revealed.
     */
    public function show(Request $request, int $interviewSession): Response
    {
        $session = $request->user()->interviewSessions()->findOrFail($interviewSession);

        return Inertia::render('interviews/Show', [
            'session' => [
                'id' => $session->id,
                'category' => $session->category->label(),
                'level' => $session->level->label(),
                'duration' => $session->duration_minutes->label(),
                'status' => $session->status->value,
                'statusLabel' => $session->status->label(),
                'createdAt' => $session->created_at?->toIso8601String(),
                'canEditSetup' => $session->status === InterviewStatus::Draft,
            ],
        ]);
    }

    private function currentDraft(Request $request): ?InterviewSession
    {
        return $request->user()->interviewSessions()
            ->where('status', InterviewStatus::Draft)
            ->latest('id')
            ->first();
    }
}
