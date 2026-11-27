<?php

namespace App\Speech;

use App\Speech\Contracts\TextToSpeech;

/**
 * Development stand-in until a TTS provider is chosen. It does not speak the
 * text: it returns a short two-tone chime as a valid WAV file, which is
 * enough to exercise fetching, playback and error handling end to end.
 */
class FakeTextToSpeech implements TextToSpeech
{
    private const SAMPLE_RATE = 16000;

    /** @var list<array{frequency: float, seconds: float}> */
    private const TONES = [
        ['frequency' => 660.0, 'seconds' => 0.35],
        ['frequency' => 880.0, 'seconds' => 0.5],
    ];

    public function synthesize(string $text): SynthesizedSpeech
    {
        return new SynthesizedSpeech($this->wav($this->samples()), 'audio/wav');
    }

    /**
     * 16-bit mono PCM samples with a short fade in/out to avoid clicks.
     */
    private function samples(): string
    {
        $pcm = '';
        $fade = (int) (self::SAMPLE_RATE * 0.02);

        foreach (self::TONES as $tone) {
            $count = (int) (self::SAMPLE_RATE * $tone['seconds']);

            for ($i = 0; $i < $count; $i++) {
                $envelope = min(1, $i / $fade, ($count - $i) / $fade);
                $value = sin(2 * M_PI * $tone['frequency'] * $i / self::SAMPLE_RATE) * 0.3 * $envelope;
                $pcm .= pack('v', (int) round($value * 32767) & 0xFFFF);
            }
        }

        return $pcm;
    }

    private function wav(string $pcm): string
    {
        $channels = 1;
        $bitsPerSample = 16;
        $byteRate = self::SAMPLE_RATE * $channels * $bitsPerSample / 8;
        $blockAlign = $channels * $bitsPerSample / 8;

        return 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, $channels, self::SAMPLE_RATE, $byteRate, $blockAlign, $bitsPerSample)
            .'data'.pack('V', strlen($pcm)).$pcm;
    }
}
