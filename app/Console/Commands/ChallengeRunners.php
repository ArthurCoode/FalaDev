<?php

namespace App\Console\Commands;

use App\Enums\ChallengeLanguage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Lists the Docker images that run the code challenges and, with --build,
 * builds them from docker/runners. Run it after deploying a change to a
 * runner (and bump faladev.challenges.image_tag).
 */
#[Signature('challenges:runners {--build : Constrói as imagens a partir de docker/runners}')]
#[Description('Mostra (ou constrói) as imagens Docker que rodam os desafios de código')]
class ChallengeRunners extends Command
{
    public function handle(): int
    {
        $docker = (string) config('faladev.challenges.docker_binary');
        $runners = array_values(array_unique(array_map(
            fn (ChallengeLanguage $language) => $language->runner(),
            ChallengeLanguage::cases(),
        )));
        $failed = false;

        foreach ($runners as $runner) {
            $image = config('faladev.challenges.image_prefix').$runner.':'.config('faladev.challenges.image_tag');

            if ($this->option('build')) {
                $this->components->task("Construindo {$image}", function () use ($docker, $image, $runner, &$failed) {
                    $process = new Process([$docker, 'build', '--tag', $image, base_path('docker/runners/'.$runner)], timeout: 1800);
                    $process->run();

                    if (! $process->isSuccessful()) {
                        $failed = true;
                        $this->newLine();
                        $this->line($process->getErrorOutput());
                    }

                    return $process->isSuccessful();
                });

                continue;
            }

            $process = new Process([$docker, 'image', 'inspect', '--format', '{{.Size}}', $image], timeout: 30);
            $process->run();

            if ($process->isSuccessful()) {
                $this->components->twoColumnDetail($image, round((int) trim($process->getOutput()) / 1_048_576).' MB');
            } else {
                $failed = true;
                $this->components->twoColumnDetail($image, '<fg=red>não encontrada</>');
            }
        }

        if ($failed && ! $this->option('build')) {
            $this->components->warn('Construa as imagens com: php artisan challenges:runners --build');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
