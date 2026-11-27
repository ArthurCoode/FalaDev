<?php

namespace App\Providers;

use App\Interview\Contracts\Interviewer;
use App\Interview\FakeInterviewer;
use App\Speech\Contracts\SpeechToText;
use App\Speech\Contracts\TextToSpeech;
use App\Speech\ElevenLabsTextToSpeech;
use App\Speech\FakeSpeechToText;
use App\Speech\FakeTextToSpeech;
use App\Speech\PrerecordedPhrases;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Speech providers are swappable through config/faladev.php.
        $this->app->bind(TextToSpeech::class, fn () => match (config('faladev.speech.tts')) {
            'fake' => new FakeTextToSpeech,
            'elevenlabs' => new ElevenLabsTextToSpeech(
                apiKey: (string) config('services.elevenlabs.key'),
                voiceId: config('services.elevenlabs.voice_id'),
                modelId: config('services.elevenlabs.model_id'),
                outputFormat: config('services.elevenlabs.output_format'),
                baseUrl: config('services.elevenlabs.base_url'),
                timeoutSeconds: config('services.elevenlabs.timeout'),
            ),
            default => throw new InvalidArgumentException('Unsupported TTS driver: '.config('faladev.speech.tts')),
        });

        $this->app->bind(SpeechToText::class, fn () => match (config('faladev.speech.stt')) {
            'fake' => new FakeSpeechToText,
            default => throw new InvalidArgumentException('Unsupported STT driver: '.config('faladev.speech.stt')),
        });

        $this->app->bind(Interviewer::class, fn () => match (config('faladev.interview.interviewer')) {
            'fake' => new FakeInterviewer,
            default => throw new InvalidArgumentException('Unsupported interviewer driver: '.config('faladev.interview.interviewer')),
        });

        $this->app->bind(PrerecordedPhrases::class, fn () => new PrerecordedPhrases(
            directory: public_path(config('faladev.speech.prerecorded_path')),
            publicPath: config('faladev.speech.prerecorded_path'),
            voiceSettings: [
                config('services.elevenlabs.voice_id'),
                config('services.elevenlabs.model_id'),
                config('services.elevenlabs.output_format'),
            ],
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
