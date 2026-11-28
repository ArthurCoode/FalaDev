<?php

namespace App\Console\Commands;

use App\Challenges\Challenge;
use App\Challenges\ChallengeCatalog;
use App\Challenges\Running\ChallengeJudge;
use App\Challenges\Running\JudgeResult;
use App\Enums\ChallengeLanguage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Proves the challenges are right, in the real runners: the reference
 * solution passes every test and the starter code does not.
 */
#[Signature('challenges:verify {language? : Só uma linguagem (javascript, python...)} {--slug= : Só um desafio}')]
#[Description('Roda a solução de referência e o código inicial de cada desafio nos contêineres')]
class VerifyChallenges extends Command
{
    public function handle(ChallengeCatalog $catalog, ChallengeJudge $judge): int
    {
        $language = $this->argument('language') ? ChallengeLanguage::tryFrom((string) $this->argument('language')) : null;

        if ($this->argument('language') && $language === null) {
            $this->components->error('Linguagem desconhecida. Use uma de: '.implode(', ', array_column(ChallengeLanguage::cases(), 'value')).'.');

            return self::INVALID;
        }

        $challenges = array_filter(
            $language ? $catalog->forLanguage($language) : $catalog->all(),
            fn (Challenge $challenge) => ! $this->option('slug') || $challenge->slug === $this->option('slug'),
        );
        $failures = 0;

        foreach ($challenges as $challenge) {
            $solution = $judge->judge($challenge, $challenge->solution);
            $starter = $judge->judge($challenge, $challenge->starter());
            $problems = [];

            if (! $solution->allPassed()) {
                $problems[] = 'a solução de referência falhou: '.$this->describe($solution);
            }

            if ($starter->status !== JudgeResult::OK) {
                $problems[] = 'o código inicial não roda: '.$this->describe($starter);
            } elseif ($starter->allPassed()) {
                $problems[] = 'o código inicial passa em todos os testes';
            }

            $label = sprintf('%-10s %s', $challenge->language->label(), $challenge->slug);
            $time = sprintf('%.1fs + %.1fs', $solution->seconds, $starter->seconds);

            if ($problems === []) {
                $this->components->twoColumnDetail($label, "<fg=green>ok</> {$time}");
            } else {
                $failures++;
                $this->components->twoColumnDetail($label, '<fg=red>falhou</>');

                foreach ($problems as $problem) {
                    $this->line("    {$problem}");
                }
            }
        }

        $this->newLine();
        $failures === 0
            ? $this->components->info(count($challenges).' desafios verificados.')
            : $this->components->error("{$failures} de ".count($challenges).' desafios com problema.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function describe(JudgeResult $result): string
    {
        if ($result->status !== JudgeResult::OK) {
            return $result->status.($result->error ? " ({$result->error})" : '');
        }

        $failed = array_filter($result->results, fn (array $test) => ! $test['passed']);

        return implode('; ', array_map(
            fn (array $test) => 'teste '.($test['index'] + 1).': '.($test['error'] ?? 'recebeu '.($test['actual'] ?? '?')),
            $failed,
        ));
    }
}
