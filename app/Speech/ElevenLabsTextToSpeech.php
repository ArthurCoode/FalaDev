<?php

namespace App\Speech;

use App\Speech\Contracts\TextToSpeech;
use App\Speech\Exceptions\SpeechSynthesisFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * ElevenLabs text-to-speech (POST /v1/text-to-speech/{voice_id}).
 * Only interviewer text is sent here; candidate audio never goes to TTS.
 */
class ElevenLabsTextToSpeech implements TextToSpeech
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $voiceId,
        private readonly string $modelId,
        private readonly string $outputFormat = 'mp3_44100_64',
        private readonly string $baseUrl = 'https://api.elevenlabs.io',
        private readonly int $timeoutSeconds = 15,
    ) {
        if ($apiKey === '') {
            throw new InvalidArgumentException('ELEVENLABS_API_KEY is not set.');
        }
    }

    public function synthesize(string $text): SynthesizedSpeech
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders(['xi-api-key' => $this->apiKey])
                ->accept('audio/mpeg')
                ->timeout($this->timeoutSeconds)
                ->post("/v1/text-to-speech/{$this->voiceId}?output_format={$this->outputFormat}", [
                    'text' => $text,
                    'model_id' => $this->modelId,
                ]);
        } catch (ConnectionException $e) {
            throw new SpeechSynthesisFailed('ElevenLabs could not be reached.', previous: $e);
        }

        if ($response->failed() || $response->body() === '') {
            // Never include the API key; the request id helps support on their side.
            throw new SpeechSynthesisFailed(sprintf(
                'ElevenLabs returned HTTP %d (request id: %s).',
                $response->status(),
                $response->header('request-id') ?: 'n/a',
            ));
        }

        return new SynthesizedSpeech($response->body(), 'audio/mpeg');
    }
}
