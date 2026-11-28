<?php

use App\Enums\ExerciseType;
use App\Models\StudyCard;
use App\Models\StudyProgress;
use App\Models\StudySession;
use App\Models\User;
use App\Study\Curriculum;
use App\Study\Exercise;
use App\Study\Lesson;
use App\Study\StudyClock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Midday in São Paulo, so "today" is the same date in UTC and locally.
    $this->travelTo(CarbonImmutable::parse('2026-03-10 15:00:00', 'UTC'));
});

function firstLesson(): Lesson
{
    return app(Curriculum::class)->units[0]->lessons[0];
}

function secondLesson(): Lesson
{
    return app(Curriculum::class)->units[0]->lessons[1];
}

function rightAnswer(Exercise $exercise): mixed
{
    return $exercise->type === ExerciseType::Order
        ? Exercise::words((string) $exercise->sentence)
        : $exercise->options[0];
}

function wrongAnswer(Exercise $exercise): mixed
{
    return $exercise->type === ExerciseType::Order ? ['nope'] : $exercise->options[1];
}

/**
 * @param  list<string>  $missKeys  exercises answered wrong on the first try
 * @return array{attempt: string, answers: list<array{key: string, value: mixed}>}
 */
function lessonPayload(Lesson $lesson, array $missKeys = [], ?string $attempt = null): array
{
    return [
        'attempt' => $attempt ?? (string) Str::uuid(),
        'answers' => array_map(fn (Exercise $exercise) => [
            'key' => $exercise->key,
            'value' => in_array($exercise->key, $missKeys, true) ? wrongAnswer($exercise) : rightAnswer($exercise),
        ], $lesson->exercises),
    ];
}

function dueCard(User $user, string $key, int $box = 0, string $dueOn = '2026-03-10'): StudyCard
{
    $card = new StudyCard(['exercise_key' => $key]);
    $card->user()->associate($user);
    $card->box = $box;
    $card->due_on = CarbonImmutable::parse($dueOn);
    $card->save();

    return $card;
}

test('guests cannot open the study track', function () {
    $this->get(route('study.index'))->assertRedirect(route('login'));
    $this->post(route('study.lessons.complete', firstLesson()->slug))->assertRedirect(route('login'));
});

test('a new user sees the track with only the first lesson of each unit open', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('study.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('study/Index')
            ->where('stats.streak', 0)
            ->where('stats.totalXp', 0)
            ->where('stats.reviewsDue', 0)
            ->where('nextLesson.slug', firstLesson()->slug)
            ->where('units.0.lessons.0.status', 'next')
            ->where('units.0.lessons.1.status', 'locked')
            ->where('units.1.lessons.0.status', 'available')
            ->where('units.1.lessons.1.status', 'locked'));
});

test('a lesson page sends its exercises without leaking other lessons', function () {
    $lesson = firstLesson();

    $this->actingAs(User::factory()->create())
        ->get(route('study.lessons.show', $lesson->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->component('study/Practice')
            ->where('mode', 'lesson')
            ->where('lesson.slug', $lesson->slug)
            ->has('exercises', count($lesson->exercises))
            ->where('exercises.0.key', $lesson->exercises[0]->key));
});

test('unknown lessons are not found and locked ones send the user back to the track', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('study.lessons.show', 'nao-existe'))->assertNotFound();
    $this->actingAs($user)->get(route('study.lessons.show', secondLesson()->slug))->assertRedirect(route('study.index'));
    $this->actingAs($user)
        ->postJson(route('study.lessons.complete', secondLesson()->slug), lessonPayload(secondLesson()))
        ->assertForbidden();
});

test('finishing a lesson grades it again on the server and counts the study day', function () {
    $user = User::factory()->create();
    $lesson = firstLesson();
    $missed = $lesson->exercises[1]->key;
    $total = count($lesson->exercises);

    $this->actingAs($user)
        ->postJson(route('study.lessons.complete', $lesson->slug), lessonPayload($lesson, [$missed]))
        ->assertOk()
        ->assertJson([
            'correct' => $total - 1,
            'total' => $total,
            'xp' => ($total - 1) * 2,
            'totalXp' => ($total - 1) * 2,
            'streak' => 1,
            'streakExtended' => true,
            'sentToReview' => 1,
            'nextLesson' => ['slug' => secondLesson()->slug],
            'reviewsDue' => 0,
        ]);

    $card = StudyCard::where('user_id', $user->id)->sole();

    expect($card->exercise_key)->toBe($missed)
        ->and($card->box)->toBe(0)
        ->and($card->due_on->toDateString())->toBe('2026-03-11');

    $this->actingAs($user)
        ->get(route('study.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('units.0.lessons.0.status', 'completed')
            ->where('units.0.lessons.0.bestCorrect', $total - 1)
            ->where('units.0.lessons.1.status', 'next')
            ->where('stats.studiedToday', true)
            ->where('stats.learning', 1));
});

test('a perfect lesson earns the bonus', function () {
    $lesson = firstLesson();
    $total = count($lesson->exercises);

    $this->actingAs(User::factory()->create())
        ->postJson(route('study.lessons.complete', $lesson->slug), lessonPayload($lesson))
        ->assertJson(['correct' => $total, 'xp' => $total * 2 + 5, 'sentToReview' => 0]);
});

test('sending the same attempt twice counts it once', function () {
    $user = User::factory()->create();
    $payload = lessonPayload(firstLesson(), [firstLesson()->exercises[0]->key]);

    $first = $this->actingAs($user)->postJson(route('study.lessons.complete', firstLesson()->slug), $payload)->json();
    $second = $this->actingAs($user)->postJson(route('study.lessons.complete', firstLesson()->slug), $payload)->json();

    expect($second)->toBe($first)
        ->and(StudySession::where('user_id', $user->id)->count())->toBe(1)
        ->and(StudyProgress::where('user_id', $user->id)->sole()->total_xp)->toBe($first['xp']);
});

test('a lesson must be finished with every exercise answered', function () {
    $payload = lessonPayload(firstLesson());
    array_pop($payload['answers']);

    $this->actingAs(User::factory()->create())
        ->postJson(route('study.lessons.complete', firstLesson()->slug), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('answers');
});

test('the completion request is validated', function (array $payload, string $field) {
    $this->actingAs(User::factory()->create())
        ->postJson(route('study.lessons.complete', firstLesson()->slug), $payload)
        ->assertJsonValidationErrors($field);
})->with([
    'no attempt' => [['answers' => [['key' => 'a', 'value' => 'b']]], 'attempt'],
    'attempt is not a uuid' => [['attempt' => '123', 'answers' => [['key' => 'a', 'value' => 'b']]], 'attempt'],
    'no answers' => [['attempt' => '0b4a3c33-6a5f-4c7e-9e57-2a3d9f0e1c11', 'answers' => []], 'answers'],
    'repeated key' => [['attempt' => '0b4a3c33-6a5f-4c7e-9e57-2a3d9f0e1c11', 'answers' => [['key' => 'a', 'value' => 'b'], ['key' => 'a', 'value' => 'c']]], 'answers.1.key'],
]);

test('finishing a lesson again keeps the best score and the unlocked lessons', function () {
    $user = User::factory()->create();
    $lesson = firstLesson();
    $keys = array_map(fn (Exercise $exercise) => $exercise->key, $lesson->exercises);

    $this->actingAs($user)->postJson(route('study.lessons.complete', $lesson->slug), lessonPayload($lesson));
    $this->actingAs($user)->postJson(route('study.lessons.complete', $lesson->slug), lessonPayload($lesson, $keys));

    $this->actingAs($user)
        ->get(route('study.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('units.0.lessons.0.bestCorrect', count($keys))
            ->where('units.0.lessons.1.status', 'next'));
});

test('the review shows the phrases due today and nothing else', function () {
    $user = User::factory()->create();
    $exercises = firstLesson()->exercises;
    dueCard($user, $exercises[0]->key, dueOn: '2026-03-09');
    dueCard($user, $exercises[1]->key, dueOn: '2026-03-10');
    dueCard($user, $exercises[2]->key, dueOn: '2026-03-11');
    dueCard(User::factory()->create(), $exercises[3]->key);

    $this->actingAs($user)
        ->get(route('study.review'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('study/Practice')
            ->where('mode', 'review')
            ->has('exercises', 2)
            ->where('exercises.0.key', $exercises[0]->key)
            ->where('exercises.1.key', $exercises[1]->key));
});

test('with nothing due the review sends the user back to the track', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('study.review'))
        ->assertRedirect(route('study.index'));
});

test('a review moves remembered phrases up and missed ones back', function () {
    $user = User::factory()->create();
    [$first, $second] = firstLesson()->exercises;
    $remembered = dueCard($user, $first->key, box: 1);
    $forgotten = dueCard($user, $second->key, box: 3);

    $this->actingAs($user)
        ->postJson(route('study.review.complete'), [
            'attempt' => (string) Str::uuid(),
            'answers' => [
                ['key' => $first->key, 'value' => rightAnswer($first)],
                ['key' => $second->key, 'value' => wrongAnswer($second)],
            ],
        ])
        ->assertOk()
        ->assertJson(['correct' => 1, 'total' => 2, 'sentToReview' => 1, 'mastered' => 0, 'streak' => 1]);

    expect($remembered->fresh()->box)->toBe(2)
        ->and($remembered->fresh()->due_on->toDateString())->toBe('2026-03-17')
        ->and($forgotten->fresh()->box)->toBe(0)
        ->and($forgotten->fresh()->lapses)->toBe(1)
        ->and($forgotten->fresh()->due_on->toDateString())->toBe('2026-03-11');
});

test('a phrase right in the last box is mastered', function () {
    $user = User::factory()->create();
    $exercise = firstLesson()->exercises[0];
    $card = dueCard($user, $exercise->key, box: 4);

    $this->actingAs($user)
        ->postJson(route('study.review.complete'), [
            'attempt' => (string) Str::uuid(),
            'answers' => [['key' => $exercise->key, 'value' => rightAnswer($exercise)]],
        ])
        ->assertJson(['mastered' => 1]);

    expect($card->fresh()->mastered_at)->not->toBeNull();
});

test('answers for phrases that are not due are ignored', function () {
    $user = User::factory()->create();
    [$first, $second] = firstLesson()->exercises;
    dueCard($user, $first->key);
    $later = dueCard($user, $second->key, dueOn: '2026-03-20');

    $this->actingAs($user)
        ->postJson(route('study.review.complete'), [
            'attempt' => (string) Str::uuid(),
            'answers' => [
                ['key' => $first->key, 'value' => rightAnswer($first)],
                ['key' => $second->key, 'value' => wrongAnswer($second)],
                ['key' => 'invented.key', 'value' => 'x'],
            ],
        ])
        ->assertJson(['total' => 1, 'correct' => 1]);

    expect($later->fresh()->box)->toBe(0)
        ->and($later->fresh()->lapses)->toBe(0);

    $this->actingAs($user)
        ->postJson(route('study.review.complete'), [
            'attempt' => (string) Str::uuid(),
            'answers' => [['key' => $second->key, 'value' => rightAnswer($second)]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('answers');
});

test('phrases that left the curriculum leave the review queue', function () {
    $user = User::factory()->create();
    $exercise = firstLesson()->exercises[0];
    dueCard($user, $exercise->key);
    $gone = dueCard($user, 'removed-lesson.removed');

    $this->actingAs($user)
        ->get(route('study.review'))
        ->assertInertia(fn (Assert $page) => $page->has('exercises', 1));

    $this->actingAs($user)->postJson(route('study.review.complete'), [
        'attempt' => (string) Str::uuid(),
        'answers' => [
            ['key' => $exercise->key, 'value' => rightAnswer($exercise)],
            ['key' => 'removed-lesson.removed', 'value' => 'x'],
        ],
    ])->assertJson(['total' => 1]);

    expect($gone->fresh())->toBeNull();
});

test('a review session holds at most one batch', function () {
    config(['faladev.study.review_batch' => 3]);
    $user = User::factory()->create();

    foreach (firstLesson()->exercises as $exercise) {
        dueCard($user, $exercise->key);
    }

    $this->actingAs($user)
        ->get(route('study.review'))
        ->assertInertia(fn (Assert $page) => $page->has('exercises', 3));
});

test('the streak counts study days in the configured time zone', function () {
    $user = User::factory()->create();
    $lesson = firstLesson();

    // 23:30 in São Paulo on the 10th (02:30 UTC on the 11th)...
    $this->travelTo(CarbonImmutable::parse('2026-03-11 02:30:00', 'UTC'));
    $this->actingAs($user)->postJson(route('study.lessons.complete', $lesson->slug), lessonPayload($lesson));

    // ...and 08:00 in São Paulo on the 11th: two days in a row.
    $this->travelTo(CarbonImmutable::parse('2026-03-11 11:00:00', 'UTC'));
    $this->actingAs($user)
        ->postJson(route('study.lessons.complete', $lesson->slug), lessonPayload($lesson))
        ->assertJson(['streak' => 2]);

    expect(StudySession::where('user_id', $user->id)->pluck('study_on')->map->toDateString()->all())
        ->toBe(['2026-03-10', '2026-03-11']);
});

test('the dashboard shows the streak and what to study next', function () {
    $user = User::factory()->create();
    dueCard($user, firstLesson()->exercises[0]->key);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('study.streak', 0)
            ->where('study.reviewsDue', 1)
            ->where('study.nextLesson.slug', firstLesson()->slug)
            ->where('study.reviewsDue', fn ($due) => $due === 1));
});

test('the menu badge counts the reviews due', function () {
    $user = User::factory()->create();
    dueCard($user, firstLesson()->exercises[0]->key);

    $this->actingAs($user)
        ->get(route('study.index'))
        ->assertInertia(fn (Assert $page) => $page->where('study.reviewsDue', 1));
});

test('deleting the account deletes the study data', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson(route('study.lessons.complete', firstLesson()->slug), lessonPayload(firstLesson(), [firstLesson()->exercises[0]->key]));

    $user->delete();

    expect(StudyProgress::count())->toBe(0)
        ->and(StudySession::count())->toBe(0)
        ->and(StudyCard::count())->toBe(0);
});

test('today is computed once per call and is a date in the study time zone', function () {
    expect(StudyClock::today()->format('H:i'))->toBe('00:00')
        ->and(StudyClock::today()->getTimezone()->getName())->toBe('America/Sao_Paulo');
});
