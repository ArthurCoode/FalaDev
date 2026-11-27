<?php

namespace App\Providers;

use App\Speech\Contracts\TextToSpeech;
use App\Speech\ElevenLabsTextToSpeech;
use App\Speech\FakeTextToSpeech;
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
