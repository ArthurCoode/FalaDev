<?php

namespace App\Challenges\Running;

use App\Enums\ChallengeLanguage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Runs each program in a new container of its language's image
 * (docker/runners): no network, read-only file system with a small /tmp,
 * limited memory, CPU and processes, no Linux capabilities, as the
 * "nobody" user. The program goes in on stdin; the container is removed
 * when it ends, and killed when it takes too long or prints too much.
 */
class DockerCodeRunner implements CodeRunner
{
    /** Seconds to start and remove a container, on top of the time limit. */
    private const DOCKER_OVERHEAD = 10;

    public function __construct(
        private readonly string $docker,
        private readonly string $imagePrefix,
        private readonly string $imageTag,
        private readonly int $timeLimit,
        private readonly string $memory,
        private readonly string $cpus,
        private readonly int $maxOutputBytes,
    ) {}

    public function image(ChallengeLanguage $language): string
    {
        return $this->imagePrefix.$language->runner().':'.$this->imageTag;
    }

    public function execute(Program $program): Execution
    {
        $name = 'faladev-run-'.Str::lower(Str::random(20));
        $process = new Process(
            command: $this->command($name, $this->image($program->language)),
            input: Tar::pack($program->files),
            timeout: $this->timeLimit + self::DOCKER_OVERHEAD,
        );

        $stdout = '';
        $stderr = '';
        $timedOut = false;
        $outputLimitHit = false;
        $started = microtime(true);

        try {
            $process->start();

            while ($process->isRunning()) {
                if (strlen($process->getOutput()) + strlen($process->getErrorOutput()) > $this->maxOutputBytes) {
                    $outputLimitHit = true;
                    break;
                }

                if (microtime(true) - $started > $this->timeLimit + self::DOCKER_OVERHEAD) {
                    $timedOut = true;
                    break;
                }

                usleep(20_000);
            }

            if ($process->isRunning()) {
                $process->stop(0);
                $this->remove($name);
            }

            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
        } catch (Throwable $error) {
            Log::error('Code runner failed to start Docker.', ['error' => $error->getMessage()]);
            $this->remove($name);

            return Execution::unavailable($error->getMessage());
        }

        $seconds = microtime(true) - $started;
        $exitCode = $process->getExitCode();

        // 125: Docker could not create the container (no daemon, no image).
        if (! $timedOut && ! $outputLimitHit && ($exitCode === 125 || $exitCode === 127)) {
            Log::error('Code runner could not start a container.', [
                'image' => $this->image($program->language),
                'exit_code' => $exitCode,
                'error' => Str::limit($stderr, 2000),
            ]);

            return Execution::unavailable($stderr);
        }

        // The "timeout" inside the container kills the program with SIGKILL
        // (exit 137) at the time limit; 137 earlier means the memory limit.
        if ($exitCode === 137 && $seconds >= $this->timeLimit - 0.5) {
            $timedOut = true;
        }

        return new Execution(
            exitCode: $exitCode,
            stdout: substr($stdout, 0, $this->maxOutputBytes),
            stderr: substr($stderr, 0, $this->maxOutputBytes),
            seconds: $seconds,
            timedOut: $timedOut,
            outputLimitHit: $outputLimitHit,
        );
    }

    /**
     * @return list<string>
     */
    private function command(string $name, string $image): array
    {
        return [
            $this->docker, 'run',
            '--rm', '--interactive',
            // An init process as PID 1: the kernel ignores SIGKILL sent to
            // PID 1 from inside, so "timeout" could not stop the program.
            '--init',
            '--name', $name,
            '--pull', 'never',
            '--network', 'none',
            '--memory', $this->memory,
            '--memory-swap', $this->memory,
            '--cpus', $this->cpus,
            '--pids-limit', '128',
            '--ulimit', 'fsize=16777216',
            '--read-only',
            '--tmpfs', '/tmp:rw,exec,nosuid,nodev,size=256m',
            '--cap-drop', 'ALL',
            '--security-opt', 'no-new-privileges',
            '--user', '65534:65534',
            $image,
            'timeout', '-s', 'KILL', (string) $this->timeLimit, '/runner/run',
        ];
    }

    private function remove(string $name): void
    {
        try {
            (new Process([$this->docker, 'rm', '--force', $name], timeout: 15))->run();
        } catch (Throwable) {
            // Already gone.
        }
    }
}
