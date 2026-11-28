<?php

namespace App\Study;

use App\Challenges\Challenge;
use App\Enums\StudySessionKind;
use App\Models\ChallengeSolution;
use App\Models\StudyCard;
use App\Models\StudyProgress;
use App\Models\StudySession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Records a finished lesson or review: grades the first-try answers again on
 * the server, updates the review queue, the XP and the streak.
 *
 * Finishing is idempotent: the page sends an attempt id, and sending the same
 * attempt again (a retry after a network error, a double click) returns the
 * first result without counting anything twice. Writes lock the user's
 * progress row, so two tabs cannot interleave.
 */
final class StudyRecorder
{
    /** XP for each answer right on the first try. */
    public const XP_PER_CORRECT = 2;

    /** Extra XP when every answer was right on the first try. */
    public const XP_PERFECT_BONUS = 5;

    /** XP for solving a code challenge the first time. */
    public const XP_CHALLENGE = 10;

    public function __construct(private readonly Curriculum $curriculum) {}

    /**
     * @param  array<string, mixed>  $answers  first-try answer per exercise key
     * @return array<string, mixed> the outcome shown on the result screen
     */
    public function completeLesson(User $user, Lesson $lesson, string $attemptId, array $answers): array
    {
        $expected = array_map(fn (Exercise $exercise) => $exercise->key, $lesson->exercises);
        $given = array_keys($answers);
        sort($expected);
        sort($given);

        if ($expected !== $given) {
            throw ValidationException::withMessages([
                'answers' => 'Responda todos os exercícios da lição.',
            ]);
        }

        return $this->record($user, $attemptId, StudySessionKind::Lesson, $lesson->slug, function (CarbonImmutable $today) use ($user, $lesson, $answers): array {
            $correct = 0;
            $missed = 0;

            foreach ($lesson->exercises as $exercise) {
                if ($exercise->isCorrect($answers[$exercise->key])) {
                    $correct++;

                    continue;
                }

                // Missed on the first try: it comes back in the review.
                $card = StudyCard::query()
                    ->where('user_id', $user->id)
                    ->where('exercise_key', $exercise->key)
                    ->first();

                if ($card === null) {
                    $card = new StudyCard(['exercise_key' => $exercise->key]);
                    $card->user()->associate($user);
                }

                ReviewSchedule::missed($card, $today);
                $card->save();
                $missed++;
            }

            return ['correct' => $correct, 'total' => count($lesson->exercises), 'sentToReview' => $missed, 'mastered' => 0];
        });
    }

    /**
     * Answers for phrases that are no longer due (already reviewed in another
     * tab) are ignored; at least one must still be due.
     *
     * @param  array<string, mixed>  $answers  answer per exercise key
     * @return array<string, mixed>
     */
    public function completeReview(User $user, string $attemptId, array $answers): array
    {
        return $this->record($user, $attemptId, StudySessionKind::Review, null, function (CarbonImmutable $today) use ($user, $answers): array {
            $cards = StudyCard::query()
                ->where('user_id', $user->id)
                ->dueOn($today)
                ->whereIn('exercise_key', array_keys($answers))
                ->get();

            if ($cards->isEmpty()) {
                throw ValidationException::withMessages([
                    'answers' => 'Essas frases já foram revisadas.',
                ]);
            }

            $correct = 0;
            $mastered = 0;
            $missed = 0;

            foreach ($cards as $card) {
                $exercise = $this->curriculum->exercise($card->exercise_key);

                if ($exercise === null) {
                    // The exercise left the curriculum: it leaves the queue too.
                    $card->delete();

                    continue;
                }

                $card->last_reviewed_at = CarbonImmutable::now();

                if ($exercise->isCorrect($answers[$card->exercise_key])) {
                    $correct++;
                    $mastered += ReviewSchedule::remembered($card, $today) ? 1 : 0;
                } else {
                    ReviewSchedule::missed($card, $today);
                    $missed++;
                }

                $card->save();
            }

            return ['correct' => $correct, 'total' => $correct + $missed, 'sentToReview' => $missed, 'mastered' => $mastered];
        });
    }

    /**
     * Records a passing run of a code challenge: keeps the code, and counts
     * the study day. Only the first solve earns XP. The tests run in the
     * browser, so this trusts the page's report.
     *
     * @return array<string, mixed>
     */
    public function solveChallenge(User $user, Challenge $challenge, string $code): array
    {
        return $this->record($user, (string) Str::uuid(), StudySessionKind::Challenge, $challenge->slug, function () use ($user, $challenge, $code): array {
            $solution = ChallengeSolution::query()
                ->where('user_id', $user->id)
                ->where('challenge_slug', $challenge->slug)
                ->first();
            $first = $solution === null;

            if ($first) {
                $solution = new ChallengeSolution([
                    'challenge_slug' => $challenge->slug,
                    'first_solved_at' => CarbonImmutable::now(),
                ]);
                $solution->user()->associate($user);
            }

            $solution->code = $code;
            $solution->last_solved_at = CarbonImmutable::now();
            $solution->save();

            return [
                'correct' => 1,
                'total' => 1,
                'sentToReview' => 0,
                'mastered' => 0,
                'firstSolve' => $first,
                'xp' => $first ? self::XP_CHALLENGE : 0,
            ];
        });
    }

    /**
     * @param  callable(CarbonImmutable): array{correct: int, total: int, sentToReview: int, mastered: int, xp?: int}  $grade
     * @return array<string, mixed>
     */
    private function record(User $user, string $attemptId, StudySessionKind $kind, ?string $lessonSlug, callable $grade): array
    {
        return DB::transaction(function () use ($user, $attemptId, $kind, $lessonSlug, $grade): array {
            $progress = $this->lockProgress($user);

            $previous = StudySession::query()
                ->where('user_id', $user->id)
                ->where('attempt_id', $attemptId)
                ->first();

            if ($previous !== null) {
                return $previous->outcome;
            }

            $today = StudyClock::today();
            $result = $grade($today);
            $xp = $result['xp'] ?? $result['correct'] * self::XP_PER_CORRECT
                + ($result['total'] > 0 && $result['correct'] === $result['total'] ? self::XP_PERFECT_BONUS : 0);

            $streak = $progress->recordStudyDay($today);
            $progress->total_xp += $xp;
            $progress->save();

            $outcome = [
                ...$result,
                'xp' => $xp,
                'totalXp' => $progress->total_xp,
                'streak' => $progress->current_streak,
                'longestStreak' => $progress->longest_streak,
                'streakExtended' => $streak['extended'],
                'freezesUsed' => $streak['freezesUsed'],
                'freezeEarned' => $streak['freezeEarned'],
                'freezes' => $progress->freezes,
            ];

            $session = new StudySession([
                'attempt_id' => $attemptId,
                'kind' => $kind,
                'lesson_slug' => $lessonSlug,
                'correct' => $result['correct'],
                'total' => $result['total'],
                'xp' => $xp,
                'study_on' => $today->toDateString(),
                'outcome' => $outcome,
            ]);
            $session->user()->associate($user);
            $session->save();

            return $outcome;
        });
    }

    /**
     * The user's progress row, created on first use and locked until the
     * transaction ends.
     */
    private function lockProgress(User $user): StudyProgress
    {
        // insertOrIgnore survives two first lessons finishing at once.
        StudyProgress::query()->insertOrIgnore([
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return StudyProgress::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
