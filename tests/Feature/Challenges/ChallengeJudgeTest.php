<?php

use App\Challenges\Challenge;
use App\Challenges\ChallengeCatalog;
use App\Challenges\Running\ChallengeJudge;
use App\Challenges\Running\CodeRunner;
use App\Challenges\Running\Execution;
use App\Challenges\Running\JudgeResult;
use App\Challenges\Running\Program;
use App\Challenges\Running\Tar;
use Illuminate\Support\Facades\Cache;

/**
 * A runner that answers with whatever the closure prints for the program.
 *
 * @param  Closure(Program): Execution  $answer
 */
function judgeWith(Closure $answer, ?array &$programs = null): ChallengeJudge
{
    $programs = [];
    $runner = new class($answer, $programs) implements CodeRunner
    {
        /** @param  list<Program>  $programs */
        public function __construct(private Closure $answer, private array &$programs) {}

        public function execute(Program $program): Execution
        {
            $this->programs[] = $program;

            return ($this->answer)($program);
        }
    };

    return new ChallengeJudge($runner, maxConcurrent: 2, timeLimit: 10, slotWait: 0);
}

function twoSum(): Challenge
{
    return app(ChallengeCatalog::class)->find('two-sum');
}

/**
 * @param  list<string>  $lines  "<index> ok <json>" or "<index> err <json>"
 */
function printed(Program $program, array $lines, string $before = ''): string
{
    return $before.implode('', array_map(fn (string $line) => "\n{$program->marker}{$line}\n", $lines));
}

test('the tests are graded on the server against the expected values', function () {
    $judge = judgeWith(fn (Program $p) => new Execution(0, printed($p, [
        '0 ok [0,1]',
        '1 ok [2,1]',
        '2 err "TypeError: boom (line 3)"',
        '3 ok [2.0,4]',
        '4 ok undefined',
    ]), '', 0.4));

    $result = $judge->judge(twoSum(), 'code');

    expect($result->status)->toBe(JudgeResult::OK)
        ->and($result->allPassed())->toBeFalse()
        ->and($result->results)->toBe([
            ['index' => 0, 'passed' => true, 'actual' => '[0, 1]'],
            ['index' => 1, 'passed' => false, 'actual' => '[2, 1]'],
            ['index' => 2, 'passed' => false, 'error' => 'TypeError: boom (line 3)'],
            // 2.0 is the number 2: a float from Python or JavaScript is right.
            ['index' => 3, 'passed' => true, 'actual' => '[2, 4]'],
            ['index' => 4, 'passed' => false, 'actual' => 'undefined'],
        ]);
});

test('every test passing is a solve', function () {
    $tests = twoSum()->tests;
    $judge = judgeWith(fn (Program $p) => new Execution(0, printed($p, array_map(
        fn (int $index) => $index.' ok '.json_encode($tests[$index]['expected']),
        array_keys($tests),
    )), '', 0.4));

    expect($judge->judge(twoSum(), 'code')->allPassed())->toBeTrue();
});

test('booleans and numbers are never equal', function () {
    $challenge = app(ChallengeCatalog::class)->find('valid-palindrome');
    $judge = judgeWith(fn (Program $p) => new Execution(0, printed($p, ['0 ok 1']), '', 0.4));

    expect($judge->judge($challenge, 'code')->results[0]['passed'])->toBeFalse();
});

test('what the program prints is kept apart from the results', function () {
    $judge = judgeWith(fn (Program $p) => new Execution(
        0,
        printed($p, ['0 ok [0,1]'], "debug [2, 7]\nsecond line"),
        "a warning\n",
        0.4,
    ));

    $result = $judge->judge(twoSum(), 'code');

    expect($result->logs)->toBe(['debug [2, 7]', 'second line', 'a warning'])
        ->and($result->results[1]['error'])->toContain('stopped before this test');
});

test('a program that does not compile reports the compiler message without container paths', function () {
    $judge = judgeWith(fn () => new Execution(1, '', "# command-line-arguments\n/tmp/code/./solution.go:4:2: declared and not used: x\n", 0.9));

    $result = $judge->judge(twoSum(), 'code');

    expect($result->status)->toBe(JudgeResult::ERROR)
        ->and($result->error)->toBe('./solution.go:4:2: declared and not used: x');
});

test('a crash in the middle keeps the results so far and the error', function () {
    $judge = judgeWith(fn (Program $p) => new Execution(134, printed($p, ['0 ok [0,1]']), 'Stack overflow.', 1.1));

    $result = $judge->judge(twoSum(), 'code');

    expect($result->status)->toBe(JudgeResult::OK)
        ->and($result->results[0]['passed'])->toBeTrue()
        ->and($result->error)->toBe('Stack overflow.');
});

test('timeouts, too much output and a missing runner are reported', function (Execution $execution, string $status) {
    expect(judgeWith(fn () => $execution)->judge(twoSum(), 'code')->status)->toBe($status);
})->with([
    'timeout' => [new Execution(137, '', '', 10.2, timedOut: true), JudgeResult::TIMEOUT],
    'output limit' => [new Execution(null, str_repeat('x', 100), '', 2.0, outputLimitHit: true), JudgeResult::ERROR],
    'no docker' => [Execution::unavailable('Cannot connect to the Docker daemon'), JudgeResult::UNAVAILABLE],
]);

test('the program gets the arguments but never the expected values', function () {
    judgeWith(fn () => new Execution(0, '', '', 0.1), $programs)->judge(twoSum(), "function twoSum() {}\n");

    $main = $programs[0]->files['main.js'];

    expect($programs[0]->files['solution.js'])->toBe("function twoSum() {}\n")
        ->and($main)->toContain('solution([2, 7, 11, 15], 9)')
        ->and($main)->toContain($programs[0]->marker)
        ->and($main)->not->toContain('[0, 1]');
});

test('a run waits for a free slot and answers busy when there is none', function () {
    $locks = [Cache::lock('challenge-runner-slot-0', 60), Cache::lock('challenge-runner-slot-1', 60)];
    $locks[0]->get();
    $locks[1]->get();

    $ran = false;
    $result = judgeWith(function () use (&$ran) {
        $ran = true;

        return new Execution(0, '', '', 0.1);
    })->judge(twoSum(), 'code');

    expect($result->status)->toBe(JudgeResult::BUSY)
        ->and($ran)->toBeFalse();

    $locks[0]->release();
    $locks[1]->release();
});

test('the program travels as a valid tar archive', function () {
    $path = tempnam(sys_get_temp_dir(), 'faladev').'.tar';
    file_put_contents($path, Tar::pack(['main.js' => 'console.log(1)', 'solution.js' => str_repeat('a', 700)]));

    $archive = new PharData($path);
    $files = [];

    foreach ($archive as $file) {
        $files[$file->getFilename()] = file_get_contents($file->getPathname());
    }

    expect($files)->toBe(['main.js' => 'console.log(1)', 'solution.js' => str_repeat('a', 700)]);

    unset($archive);
    @unlink($path);
});
