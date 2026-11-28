<?php

namespace App\Study;

use App\Enums\StudySessionKind;
use App\Models\StudyCard;
use App\Models\StudyProgress;
use App\Models\StudySession;
use App\Models\User;

/**
 * Read side of the study track: what is unlocked, done and due for a user.
 */
final class StudyTrack
{
    public function __construct(private readonly Curriculum $curriculum) {}

    /**
     * Best first-try score per completed lesson slug.
     *
     * @return array<string, int>
     */
    public function completedLessons(User $user): array
    {
        /** @var array<string, int> */
        return StudySession::query()
            ->where('user_id', $user->id)
            ->where('kind', StudySessionKind::Lesson)
            ->groupBy('lesson_slug')
            ->selectRaw('lesson_slug, max(correct) as best')
            ->pluck('best', 'lesson_slug')
            ->map(fn ($best) => (int) $best)
            ->all();
    }

    /**
     * A unit's first lesson is always open; the others open once the one
     * before them is done.
     *
     * @param  array<string, int>  $completed
     */
    public function isUnlocked(Lesson $lesson, array $completed): bool
    {
        return $lesson->previousSlug === null || isset($completed[$lesson->previousSlug]);
    }

    /**
     * The first open lesson not done yet, following the track's order.
     *
     * @param  array<string, int>  $completed
     */
    public function nextLesson(array $completed): ?Lesson
    {
        foreach ($this->curriculum->units as $unit) {
            foreach ($unit->lessons as $lesson) {
                if (! isset($completed[$lesson->slug]) && $this->isUnlocked($lesson, $completed)) {
                    return $lesson;
                }
            }
        }

        return null;
    }

    public function reviewsDue(User $user): int
    {
        return StudyCard::query()
            ->where('user_id', $user->id)
            ->dueOn(StudyClock::today())
            ->count();
    }

    /**
     * The exercises of the phrases due today, at most one review batch.
     *
     * @return list<Exercise>
     */
    public function dueExercises(User $user): array
    {
        $keys = StudyCard::query()
            ->where('user_id', $user->id)
            ->dueOn(StudyClock::today())
            ->pluck('exercise_key');

        $exercises = [];

        foreach ($keys as $key) {
            $exercise = $this->curriculum->exercise($key);

            if ($exercise !== null) {
                $exercises[] = $exercise;
            }

            if (count($exercises) === (int) config('faladev.study.review_batch')) {
                break;
            }
        }

        return $exercises;
    }

    /**
     * Streak, XP and the review queue, as shown on the study page and the
     * dashboard.
     *
     * @return array<string, mixed>
     */
    public function stats(User $user): array
    {
        $today = StudyClock::today();
        $progress = StudyProgress::query()->where('user_id', $user->id)->first() ?? new StudyProgress;

        $cards = StudyCard::query()
            ->where('user_id', $user->id)
            ->selectRaw('sum(case when mastered_at is null then 1 else 0 end) as learning')
            ->selectRaw('sum(case when mastered_at is not null then 1 else 0 end) as mastered')
            ->toBase()
            ->first();

        return [
            'streak' => $progress->streakOn($today),
            'longestStreak' => $progress->longest_streak,
            'studiedToday' => $progress->studiedOn($today),
            'freezes' => $progress->freezes,
            'maxFreezes' => (int) config('faladev.study.max_freezes'),
            'freezeEveryDays' => (int) config('faladev.study.freeze_every_days'),
            'totalXp' => $progress->total_xp,
            'reviewsDue' => $this->reviewsDue($user),
            'learning' => (int) ($cards->learning ?? 0),
            'mastered' => (int) ($cards->mastered ?? 0),
        ];
    }

    /**
     * Everything the study page shows.
     *
     * @return array<string, mixed>
     */
    public function overview(User $user): array
    {
        $completed = $this->completedLessons($user);
        $next = $this->nextLesson($completed);

        $units = array_map(function (Unit $unit) use ($completed, $next): array {
            $lessons = array_map(fn (Lesson $lesson): array => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'exercises' => count($lesson->exercises),
                'bestCorrect' => $completed[$lesson->slug] ?? null,
                'status' => match (true) {
                    isset($completed[$lesson->slug]) => 'completed',
                    $lesson->slug === $next?->slug => 'next',
                    $this->isUnlocked($lesson, $completed) => 'available',
                    default => 'locked',
                },
            ], $unit->lessons);

            return [
                'slug' => $unit->slug,
                'title' => $unit->title,
                'description' => $unit->description,
                'completedCount' => count(array_filter($lessons, fn (array $lesson) => $lesson['status'] === 'completed')),
                'lessons' => $lessons,
            ];
        }, $this->curriculum->units);

        return [
            'stats' => $this->stats($user),
            'units' => $units,
            'nextLesson' => $next ? ['slug' => $next->slug, 'title' => $next->title] : null,
        ];
    }
}
