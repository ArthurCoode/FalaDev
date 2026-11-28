<?php

namespace App\Challenges\Running;

use App\Challenges\Challenge;
use App\Challenges\Languages\Toolchain;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Runs a challenge's tests against the user's code and grades them on the
 * server: the program only prints what the function returned, and the
 * expected values never leave this class.
 */
class ChallengeJudge
{
    /** Lines of the program's own output kept for the page. */
    private const MAX_LOG_LINES = 200;

    private const MAX_LOG_LINE_LENGTH = 500;

    /** Characters of the program's own output kept for the page. */
    private const MAX_LOG_CHARACTERS = 20_000;

    /** Seconds to wait for a free runner slot before answering "busy". */
    private const SLOT_WAIT = 8;

    public function __construct(
        private readonly CodeRunner $runner,
        private readonly int $maxConcurrent,
        private readonly int $timeLimit,
        private readonly float $slotWait = self::SLOT_WAIT,
    ) {}

    public function judge(Challenge $challenge, string $code): JudgeResult
    {
        $slot = $this->slot();

        if ($slot === null) {
            return new JudgeResult(JudgeResult::BUSY);
        }

        try {
            $marker = '@@faladev:'.Str::random(24).':';
            $program = new Program(
                slug: $challenge->slug,
                language: $challenge->language,
                files: $challenge->language->toolchain()->program(
                    $challenge->signature,
                    $code,
                    array_column($challenge->tests, 'args'),
                    $marker,
                ),
                marker: $marker,
            );

            return $this->grade($challenge, $program, $this->runner->execute($program));
        } finally {
            $slot->release();
        }
    }

    private function grade(Challenge $challenge, Program $program, Execution $execution): JudgeResult
    {
        if ($execution->unavailable) {
            return new JudgeResult(JudgeResult::UNAVAILABLE);
        }

        [$found, $logs] = $this->parse($execution->stdout, $program->marker);
        $stderr = $this->clean($execution->stderr);

        if ($execution->timedOut) {
            return new JudgeResult(JudgeResult::TIMEOUT, logs: $logs, seconds: $execution->seconds);
        }

        if ($execution->outputLimitHit) {
            return new JudgeResult(
                JudgeResult::ERROR,
                logs: $logs,
                error: 'The program printed too much output and was stopped. Check for a print inside a long loop.',
                seconds: $execution->seconds,
            );
        }

        if ($found === []) {
            return new JudgeResult(
                JudgeResult::ERROR,
                logs: $logs,
                error: $stderr !== '' ? $stderr : "The program ended before running the tests (exit code {$execution->exitCode}).",
                seconds: $execution->seconds,
            );
        }

        $results = [];

        foreach ($challenge->tests as $index => $test) {
            $results[] = $this->result($index, $test['expected'], $found[$index] ?? null);
        }

        // Warnings and stderr prints are the user's output too.
        if ($stderr !== '' && $execution->exitCode === 0) {
            $logs = [...$logs, ...explode("\n", $stderr)];
        }

        return new JudgeResult(
            JudgeResult::OK,
            results: $results,
            logs: $logs,
            error: $execution->exitCode === 0 || $stderr === '' ? null : $stderr,
            seconds: $execution->seconds,
        );
    }

    /**
     * The result lines by test, and every other line (the user's output).
     *
     * @return array{0: array<int, array{0: string, 1: string}>, 1: list<string>}
     */
    private function parse(string $stdout, string $marker): array
    {
        $found = [];
        $logs = [];
        $kept = 0;
        $cut = false;

        foreach (preg_split('/\r?\n/', $stdout) ?: [] as $line) {
            if (str_starts_with($line, $marker)) {
                if (preg_match('/^(\d+) (ok|err) (.*)$/s', substr($line, strlen($marker)), $match)) {
                    $found[(int) $match[1]] ??= [$match[2], $match[3]];
                }

                // The program starts each result on a new line.
                if ($logs !== [] && end($logs) === '') {
                    array_pop($logs);
                }

                continue;
            }

            if ($kept >= self::MAX_LOG_CHARACTERS || count($logs) >= self::MAX_LOG_LINES) {
                $cut = true;

                continue;
            }

            $logs[] = Str::limit($line, self::MAX_LOG_LINE_LENGTH);
            $kept += min(strlen($line), self::MAX_LOG_LINE_LENGTH);
        }

        while ($logs !== [] && trim((string) end($logs)) === '') {
            array_pop($logs);
        }

        while ($logs !== [] && trim($logs[0]) === '') {
            array_shift($logs);
        }

        if ($cut) {
            $logs[] = '… (output cut here)';
        }

        return [$found, $logs];
    }

    /**
     * @param  array{0: string, 1: string}|null  $line
     * @return array{index: int, passed: bool, actual?: string, error?: string}
     */
    private function result(int $index, mixed $expected, ?array $line): array
    {
        if ($line === null) {
            return ['index' => $index, 'passed' => false, 'error' => 'Did not run: the program stopped before this test.'];
        }

        [$kind, $payload] = $line;

        if ($kind === 'err') {
            $message = json_decode($payload, true);

            return ['index' => $index, 'passed' => false, 'error' => is_string($message) ? $message : $payload];
        }

        $actual = json_decode($payload, true, 64, JSON_BIGINT_AS_STRING);
        $valid = json_last_error() === JSON_ERROR_NONE;

        return [
            'index' => $index,
            'passed' => $valid && self::same($actual, $expected),
            'actual' => $valid ? Toolchain::display($actual) : $payload,
        ];
    }

    /**
     * Equal by value. Numbers compare by value (3.0 equals 3), so a float
     * result in Python or JavaScript is not a wrong answer.
     */
    private static function same(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual) && is_array($expected)) {
            if (! array_is_list($actual) || ! array_is_list($expected) || count($actual) !== count($expected)) {
                return false;
            }

            foreach ($expected as $index => $item) {
                if (! self::same($actual[$index], $item)) {
                    return false;
                }
            }

            return true;
        }

        if ((is_int($actual) || is_float($actual)) && (is_int($expected) || is_float($expected))) {
            return $actual == $expected;
        }

        return $actual === $expected;
    }

    /**
     * Compiler and runtime messages without the container's paths.
     */
    private function clean(string $stderr): string
    {
        $text = str_replace(['/tmp/code/', '/tmp/out/'], '', $stderr);
        // Go prints this header above its compile errors.
        $text = (string) preg_replace('/^# command-line-arguments\R/m', '', $text);

        return Str::limit(trim($text), 3000);
    }

    private function slot(): ?Lock
    {
        $deadline = microtime(true) + $this->slotWait;

        do {
            for ($slot = 0; $slot < max(1, $this->maxConcurrent); $slot++) {
                $lock = Cache::lock("challenge-runner-slot-{$slot}", $this->timeLimit + 30);

                if ($lock->get()) {
                    return $lock;
                }
            }

            usleep(250_000);
        } while (microtime(true) < $deadline);

        return null;
    }
}
