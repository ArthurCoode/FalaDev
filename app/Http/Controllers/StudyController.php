<?php

namespace App\Http\Controllers;

use App\Http\Requests\Study\CompleteStudySessionRequest;
use App\Study\Curriculum;
use App\Study\Exercise;
use App\Study\Lesson;
use App\Study\StudyRecorder;
use App\Study\StudyTrack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The study track: short lessons, the review of missed phrases and the streak.
 */
class StudyController extends Controller
{
    public function __construct(
        private readonly Curriculum $curriculum,
        private readonly StudyTrack $track,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('study/Index', $this->track->overview($request->user()));
    }

    public function lesson(Request $request, string $lesson): Response|RedirectResponse
    {
        $found = $this->findLesson($lesson);

        if (! $this->track->isUnlocked($found, $this->track->completedLessons($request->user()))) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Conclua a lição anterior para abrir esta.']);

            return to_route('study.index');
        }

        $unit = collect($this->curriculum->units)->firstWhere('slug', $found->unitSlug);

        return Inertia::render('study/Practice', [
            'mode' => 'lesson',
            'lesson' => ['slug' => $found->slug, 'title' => $found->title],
            'context' => $unit?->title,
            'exercises' => array_map(fn (Exercise $exercise) => $exercise->toPage(), $found->exercises),
        ]);
    }

    public function completeLesson(CompleteStudySessionRequest $request, StudyRecorder $recorder, string $lesson): JsonResponse
    {
        $user = $request->user();
        $found = $this->findLesson($lesson);
        $completed = $this->track->completedLessons($user);

        abort_unless($this->track->isUnlocked($found, $completed), 403, 'Conclua a lição anterior para abrir esta.');

        $outcome = $recorder->completeLesson($user, $found, (string) $request->validated('attempt'), $request->answers());

        return response()->json($this->withNextSteps($request, $outcome, $found));
    }

    public function review(Request $request): Response|RedirectResponse
    {
        $exercises = $this->track->dueExercises($request->user());

        if ($exercises === []) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Nenhuma frase para revisar hoje.']);

            return to_route('study.index');
        }

        return Inertia::render('study/Practice', [
            'mode' => 'review',
            'lesson' => null,
            'context' => 'Revisão',
            'exercises' => array_map(fn (Exercise $exercise) => $exercise->toPage(), $exercises),
        ]);
    }

    public function completeReview(CompleteStudySessionRequest $request, StudyRecorder $recorder): JsonResponse
    {
        $outcome = $recorder->completeReview($request->user(), (string) $request->validated('attempt'), $request->answers());

        return response()->json($this->withNextSteps($request, $outcome, null));
    }

    private function findLesson(string $slug): Lesson
    {
        $lesson = $this->curriculum->lesson($slug);

        abort_if($lesson === null, 404);

        return $lesson;
    }

    /**
     * Adds what the result screen offers next: the following lesson and the
     * reviews still due.
     *
     * @param  array<string, mixed>  $outcome
     * @return array<string, mixed>
     */
    private function withNextSteps(Request $request, array $outcome, ?Lesson $lesson): array
    {
        $user = $request->user();
        $next = $lesson ? $this->curriculum->nextLesson($lesson) : null;
        $next ??= $this->track->nextLesson($this->track->completedLessons($user));

        return [
            ...$outcome,
            'nextLesson' => $next && $next->slug !== $lesson?->slug ? ['slug' => $next->slug, 'title' => $next->title] : null,
            'reviewsDue' => $this->track->reviewsDue($user),
        ];
    }
}
