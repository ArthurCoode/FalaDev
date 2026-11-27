<?php

namespace App\Console\Commands;

use App\Interview\Contracts\Interviewer;
use App\Speech\Contracts\TextToSpeech;
use App\Speech\Exceptions\SpeechSynthesisFailed;
use App\Speech\PrerecordedPhrases;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Records every fixed interviewer line with the configured TTS provider.
 * Lines that already have a file are skipped, so running it again only
 * spends quota on new or edited lines.
 */
#[Signature('faladev:record-phrases {--dry-run : Só lista o que falta gravar, sem chamar o provedor}')]
#[Description('Grava em MP3 as frases fixas do entrevistador (public/audio/interviewer)')]
class RecordPhrases extends Command
{
    public function handle(PrerecordedPhrases $phrases, Interviewer $interviewer): int
    {
        $lines = array_values(array_unique([
            config('faladev.speech.sample_phrase'),
            config('faladev.interview.opening'),
            config('faladev.interview.closing'),
            ...$interviewer->fixedLines(),
        ]));

        $missing = array_values(array_filter($lines, fn (string $line) => ! $phrases->has($line)));
        $characters = array_sum(array_map(mb_strlen(...), $missing));

        $this->info(sprintf(
            '%d frases no total, %d já gravadas, %d faltando (%d caracteres).',
            count($lines),
            count($lines) - count($missing),
            count($missing),
            $characters,
        ));

        if ($missing === [] || $this->option('dry-run')) {
            return self::SUCCESS;
        }

        $tts = app(TextToSpeech::class);

        foreach ($missing as $line) {
            try {
                $speech = $tts->synthesize($line);
            } catch (SpeechSynthesisFailed $e) {
                $this->error('Falha ao gravar: '.$e->getMessage());

                return self::FAILURE;
            }

            if ($speech->mimeType !== 'audio/mpeg') {
                $this->error('O provedor configurado não gera MP3. Use FALADEV_TTS_DRIVER=elevenlabs no .env.');

                return self::FAILURE;
            }

            $phrases->store($line, $speech->audio);
            $this->line('  gravada: '.mb_strimwidth($line, 0, 70, '…'));
        }

        $this->info('Pronto.');

        return self::SUCCESS;
    }
}
