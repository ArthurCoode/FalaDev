<?php

namespace App\Study;

use App\Models\StudyCard;
use Carbon\CarbonImmutable;

/**
 * Spaced repetition with Leitner boxes. A phrase missed in a lesson enters
 * box 0 and comes back the next day. Each right answer in a review moves it
 * up one box and waits longer (1, 3, 7, 14, 30 days); a right answer in the
 * last box masters it. A wrong answer sends it back to box 0.
 */
final class ReviewSchedule
{
    /** Days to wait before the next review, per box. */
    public const INTERVALS = [1, 3, 7, 14, 30];

    /**
     * A phrase was missed (in a lesson or a review): back to the start.
     */
    public static function missed(StudyCard $card, CarbonImmutable $today): void
    {
        if ($card->exists) {
            $card->lapses += 1;
        }

        $card->box = 0;
        $card->mastered_at = null;
        $card->due_on = $today->addDays(self::INTERVALS[0]);
    }

    /**
     * A phrase was right in a review. Returns whether it is now mastered.
     */
    public static function remembered(StudyCard $card, CarbonImmutable $today): bool
    {
        if ($card->box >= count(self::INTERVALS) - 1) {
            $card->mastered_at = CarbonImmutable::now();
            $card->due_on = null;

            return true;
        }

        $card->box += 1;
        $card->due_on = $today->addDays(self::INTERVALS[$card->box]);

        return false;
    }
}
