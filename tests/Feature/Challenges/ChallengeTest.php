<?php

use App\Challenges\Challenge;
use App\Challenges\ChallengeCatalog;
use App\Challenges\Running\CodeRunner;
use App\Challenges\Running\Execution;
use App\Challenges\Running\Program;
use App\Enums\ChallengeLanguage;
use App\Enums\StudySessionKind;
use App\Models\ChallengeSolution;
use App\Models\StudyProgress;
use App\Models\StudySession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-10 15:00:00', 'UTC'));
});

function firstChallenge(): Challenge
{
    return app(ChallengeCatalog::class)->all()[0];
}

/**
 * Stands in for Docker: every test passes, or every test returns null.
 */
function fakeRunner(bool $passes): void
{
    app()->instance(CodeRunner::class, new class($passes) implements CodeRunner
    {
        public function __construct(private bool $passes) {}

        public function execute(Program $program): Execution
        {
            $challenge = app(ChallengeCatalog::class)->find($program->slug);
            $lines = array_map(
                fn (array $test, int $index) => "\n{$program->marker}{$index} ok ".($this->passes ? json_encode($test['expected']) : 'null')."\n",
                $challenge->tests,
                array_keys($challenge->tests),
            );

            return new Execution(0, 'hello from the code'.implode('', $lines), '', 0.5);
        }
    });
}

function runCode(User $user, string $slug, string $code = 'my code'): TestResponse
{
    return test()->actingAs($user)->postJson(route('challenges.run', $slug), ['code' => $code]);
}

test('guests cannot open the challenges or the tutorial', function () {
    $slug = firstChallenge()->slug;

    $this->get(route('challenges.index'))->assertRedirect(route('login'));
    $this->get(route('challenges.show', $slug))->assertRedirect(route('login'));
    $this->post(route('challenges.run', $slug))->assertRedirect(route('login'));
    $this->get(route('tutorial'))->assertRedirect(route('login'));
    $this->post(route('tutorial.dismiss'))->assertRedirect(route('login'));
});

test('the list shows every language with its six challenges and what is solved', function () {
    $user = User::factory()->create();
    $solution = new ChallengeSolution([
        'challenge_slug' => firstChallenge()->slug,
        'code' => 'function x() {}',
        'first_solved_at' => now(),
        'last_solved_at' => now(),
    ]);
    $solution->user()->associate($user)->save();

    $this->actingAs($user)
        ->get(route('challenges.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('challenges/Index')
            ->where('language', 'javascript')
            ->has('languages', count(ChallengeLanguage::cases()))
            ->where('languages.0', ['value' => 'javascript', 'label' => 'JavaScript', 'total' => 6, 'solved' => 1])
            ->where('languages.1.solved', 0)
            ->has('challenges', 6 * count(ChallengeLanguage::cases()))
            ->where('challenges.0.slug', firstChallenge()->slug)
            ->where('challenges.0.solved', true)
            ->where('challenges.1.solved', false));
});

test('the list opens on the language in the URL, else the one in the cookie', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('challenges.index', ['language' => 'go']))
        ->assertInertia(fn (Assert $page) => $page->where('language', 'go'));

    $this->actingAs($user)
        ->get(route('challenges.index', ['language' => 'cobol']))
        ->assertInertia(fn (Assert $page) => $page->where('language', 'javascript'));

    $this->actingAs($user)
        ->withUnencryptedCookie('challenge_language', 'python')
        ->get(route('challenges.index'))
        ->assertInertia(fn (Assert $page) => $page->where('language', 'python'));
});

test('a challenge page sends its statement, the tests as calls and the next challenge', function () {
    $catalog = app(ChallengeCatalog::class);
    [$first, $second] = $catalog->forLanguage(ChallengeLanguage::Python);

    $this->actingAs(User::factory()->create())
        ->get(route('challenges.show', $first->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->component('challenges/Show')
            ->where('challenge.slug', $first->slug)
            ->where('challenge.language', 'python')
            ->where('challenge.languageLabel', 'Python')
            ->where('challenge.starter', $first->starter())
            ->has('challenge.tests', count($first->tests))
            ->missing('challenge.solution')
            ->where('savedCode', null)
            ->where('solved', false)
            ->where('next.slug', $second->slug)
            ->where('xpReward', 10)
            ->where('timeLimit', config('faladev.challenges.time_limit')));
});

test('unknown challenges are not found', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('challenges.show', 'nao-existe'))->assertNotFound();
    $this->actingAs($user)->postJson(route('challenges.run', 'nao-existe'), ['code' => 'x'])->assertNotFound();
});

test('a run that passes every test keeps the code, earns XP and counts the study day', function () {
    fakeRunner(passes: true);
    $user = User::factory()->create();
    $slug = firstChallenge()->slug;

    runCode($user, $slug, 'function fizzBuzz(n) {}')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('logs', ['hello from the code'])
        ->assertJsonPath('results.0.passed', true)
        ->assertJsonPath('outcome.firstSolve', true)
        ->assertJsonPath('outcome.xp', 10)
        ->assertJsonPath('outcome.totalXp', 10)
        ->assertJsonPath('outcome.streak', 1);

    expect(ChallengeSolution::where('user_id', $user->id)->sole()->code)->toBe('function fizzBuzz(n) {}')
        ->and(StudySession::where('user_id', $user->id)->sole()->kind)->toBe(StudySessionKind::Challenge);

    $this->actingAs($user)
        ->get(route('challenges.show', $slug))
        ->assertInertia(fn (Assert $page) => $page
            ->where('solved', true)
            ->where('savedCode', 'function fizzBuzz(n) {}'));
});

test('a run with a failing test saves nothing', function () {
    fakeRunner(passes: false);
    $user = User::factory()->create();

    runCode($user, firstChallenge()->slug)
        ->assertOk()
        ->assertJsonPath('results.0.passed', false)
        ->assertJsonPath('outcome', null);

    expect(ChallengeSolution::count())->toBe(0)
        ->and(StudySession::count())->toBe(0);
});

test('solving again updates the code without more XP', function () {
    fakeRunner(passes: true);
    $user = User::factory()->create();
    $slug = firstChallenge()->slug;

    runCode($user, $slug, 'first');
    runCode($user, $slug, 'second')
        ->assertJsonPath('outcome.firstSolve', false)
        ->assertJsonPath('outcome.xp', 0)
        ->assertJsonPath('outcome.totalXp', 10);

    expect(ChallengeSolution::where('user_id', $user->id)->sole()->code)->toBe('second')
        ->and(StudyProgress::where('user_id', $user->id)->sole()->total_xp)->toBe(10);
});

test('solved challenges do not count as completed lessons', function () {
    fakeRunner(passes: true);
    $user = User::factory()->create();
    runCode($user, firstChallenge()->slug);

    $this->actingAs($user)
        ->get(route('study.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('units.0.lessons.0.status', 'next')
            ->where('stats.studiedToday', true));
});

test('with running turned off, a run is unavailable and saves nothing', function () {
    config(['faladev.challenges.runner' => 'disabled']);

    runCode(User::factory()->create(), firstChallenge()->slug)
        ->assertOk()
        ->assertJsonPath('status', 'unavailable')
        ->assertJsonPath('outcome', null);

    expect(ChallengeSolution::count())->toBe(0);
});

test('the code is required and limited in size', function (mixed $code) {
    $this->actingAs(User::factory()->create())
        ->postJson(route('challenges.run', firstChallenge()->slug), ['code' => $code])
        ->assertJsonValidationErrors('code');
})->with([
    'missing' => [null],
    'empty' => [''],
    'too long' => [str_repeat('a', 20_001)],
]);

test('deleting the account deletes the solutions', function () {
    fakeRunner(passes: true);
    $user = User::factory()->create();
    runCode($user, firstChallenge()->slug);

    $user->delete();

    expect(ChallengeSolution::count())->toBe(0);
});
