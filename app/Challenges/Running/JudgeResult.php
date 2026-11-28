<?php

namespace App\Challenges\Running;

/**
 * The outcome of running a challenge's tests against the user's code.
 */
final readonly class JudgeResult
{
    public const OK = 'ok';

    /** The code did not compile or crashed before the tests. */
    public const ERROR = 'error';

    public const TIMEOUT = 'timeout';

    /** Every runner slot was taken. */
    public const BUSY = 'busy';

    /** No runner (Docker down, image missing, running turned off). */
    public const UNAVAILABLE = 'unavailable';

    /**
     * @param  list<array{index: int, passed: bool, actual?: string, error?: string}>  $results
     * @param  list<string>  $logs  what the program printed
     */
    public function __construct(
        public string $status,
        public array $results = [],
        public array $logs = [],
        public ?string $error = null,
        public float $seconds = 0.0,
    ) {}

    public function allPassed(): bool
    {
        if ($this->status !== self::OK || $this->results === []) {
            return false;
        }

        foreach ($this->results as $result) {
            if (! $result['passed']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{status: string, results: list<array{index: int, passed: bool, actual?: string, error?: string}>, logs: list<string>, error: ?string, seconds: float}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'results' => $this->results,
            'logs' => $this->logs,
            'error' => $this->error,
            'seconds' => round($this->seconds, 1),
        ];
    }
}
