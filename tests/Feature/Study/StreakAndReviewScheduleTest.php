<?php

use App\Models\StudyCard;
use App\Models\StudyProgress;
use App\Models\User;
use App\Study\ReviewSchedule;
use App\Study\StudyClock;
use Carbon\CarbonImmutable;

function day(string $date): CarbonImmutable
{
    return CarbonImmutable::parse($date, 'America/Sao_Paulo');
}

test('the first study day starts a streak of one', function () {
    $progress = new StudyProgress;

    expect($progress->recordStudyDay(day('2026-03-02')))->toBe(['extended' => true, 'freezesUsed' => 0, 'freezeEarned' => false])
        ->and($progress->current_streak)->toBe(1)
        ->and($progress->longest_streak)->toBe(1);
});

test('studying again on the same day changes nothing', function () {
    $progress = new StudyProgress;
    $progress->recordStudyDay(day('2026-03-02'));

    expect($progress->recordStudyDay(day('2026-03-02'))['extended'])->toBeFalse()
        ->and($progress->current_streak)->toBe(1);
});

test('consecutive days grow the streak and a missed day resets it', function () {
    $progress = new StudyProgress;
    $progress->recordStudyDay(day('2026-03-02'));
    $progress->recordStudyDay(day('2026-03-03'));
    $progress->recordStudyDay(day('2026-03-04'));

    expect($progress->current_streak)->toBe(3);

    $progress->recordStudyDay(day('2026-03-06'));

    expect($progress->current_streak)->toBe(1)
        ->and($progress->longest_streak)->toBe(3);
});

test('a freeze is earned every seven days in a row, up to the maximum', function () {
    $progress = new StudyProgress;
    $earned = [];

    foreach (range(0, 20) as $offset) {
        $earned[] = $progress->recordStudyDay(day('2026-03-01')->addDays($offset))['freezeEarned'];
    }

    expect($progress->current_streak)->toBe(21)
        ->and(array_keys(array_filter($earned)))->toBe([6, 13])
        ->and($progress->freezes)->toBe(2);
});

test('freezes cover missed days and keep the streak', function () {
    $progress = new StudyProgress;
    $progress->current_streak = 10;
    $progress->longest_streak = 10;
    $progress->freezes = 2;
    $progress->last_study_on = day('2026-03-10');

    $result = $progress->recordStudyDay(day('2026-03-13'));

    expect($result['freezesUsed'])->toBe(2)
        ->and($progress->current_streak)->toBe(11)
        ->and($progress->freezes)->toBe(0);
});

test('more missed days than freezes resets the streak and keeps the freezes', function () {
    $progress = new StudyProgress;
    $progress->current_streak = 10;
    $progress->freezes = 1;
    $progress->last_study_on = day('2026-03-10');

    $progress->recordStudyDay(day('2026-03-13'));

    expect($progress->current_streak)->toBe(1)
        ->and($progress->freezes)->toBe(1);
});

test('the streak shown today survives while freezes can cover the gap', function () {
    $progress = new StudyProgress;
    $progress->current_streak = 5;
    $progress->freezes = 1;
    $progress->last_study_on = day('2026-03-10');

    expect($progress->streakOn(day('2026-03-10')))->toBe(5)
        ->and($progress->streakOn(day('2026-03-11')))->toBe(5)
        ->and($progress->streakOn(day('2026-03-12')))->toBe(5)
        ->and($progress->streakOn(day('2026-03-13')))->toBe(0)
        ->and((new StudyProgress)->streakOn(day('2026-03-13')))->toBe(0);
});

test('a stored study date compares by calendar day, whatever its time zone', function () {
    $user = User::factory()->create();
    $progress = new StudyProgress;
    $progress->user()->associate($user);
    $progress->recordStudyDay(day('2026-03-10'));
    $progress->save();

    $fresh = $progress->fresh();

    expect($fresh->studiedOn(day('2026-03-10')))->toBeTrue()
        ->and($fresh->streakOn(day('2026-03-11')))->toBe(1);
});

test('the study day follows the configured time zone', function () {
    // 01:30 UTC on the 11th is still 22:30 of the 10th in São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-03-11 01:30:00', 'UTC'));

    expect(StudyClock::today()->toDateString())->toBe('2026-03-10');
});

test('a missed phrase comes back after 1, 3, 7, 14 and 30 days, then is mastered', function () {
    $card = new StudyCard;
    $today = day('2026-03-01');

    ReviewSchedule::missed($card, $today);
    $wait = fn () => (int) CarbonImmutable::parse($today->toDateString())->diffInDays($card->due_on->toDateString());
    $waits = [$wait()];

    while (! ReviewSchedule::remembered($card, $today)) {
        $waits[] = $wait();
    }

    expect($waits)->toBe([1, 3, 7, 14, 30])
        ->and($card->mastered_at)->not->toBeNull()
        ->and($card->due_on)->toBeNull();
});

test('missing a phrase in a review sends it back to the first box', function () {
    $card = new StudyCard;
    $card->box = 3;
    $card->exists = true;

    ReviewSchedule::missed($card, day('2026-03-01'));

    expect($card->box)->toBe(0)
        ->and($card->lapses)->toBe(1)
        ->and($card->due_on->toDateString())->toBe('2026-03-02');
});
