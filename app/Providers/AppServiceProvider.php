<?php

namespace App\Providers;

use App\Challenges\ChallengeCatalog;
use App\Challenges\Running\ChallengeJudge;
use App\Challenges\Running\CodeRunner;
use App\Challenges\Running\DisabledCodeRunner;
use App\Challenges\Running\DockerCodeRunner;
use App\Evaluation\Contracts\Evaluator;
use App\Evaluation\FakeEvaluator;
use App\Interview\Contracts\Interviewer;
use App\Interview\FakeInterviewer;
use App\Models\User;
use App\Speech\Contracts\SpeechToText;
use App\Speech\Contracts\TextToSpeech;
use App\Speech\ElevenLabsTextToSpeech;
use App\Speech\FakeSpeechToText;
use App\Speech\FakeTextToSpeech;
use App\Speech\PrerecordedPhrases;
use App\Study\Curriculum;
use Carbon\CarbonImmutable;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        $this->app->bind(Evaluator::class, fn () => match (config('faladev.evaluation.evaluator')) {
            'fake' => new FakeEvaluator,
            default => throw new InvalidArgumentException('Unsupported evaluator driver: '.config('faladev.evaluation.evaluator')),
        });

        // Loaded and checked once per request; see resources/study/curriculum.php.
        $this->app->singleton(Curriculum::class, fn () => Curriculum::load(resource_path('study/curriculum.php')));
        $this->app->singleton(ChallengeCatalog::class, fn () => ChallengeCatalog::load(resource_path('challenges')));

        // Code challenges run in Docker; see config/faladev.php (challenges).
        $this->app->bind(CodeRunner::class, fn () => match (config('faladev.challenges.runner')) {
            'docker' => new DockerCodeRunner(
                docker: (string) config('faladev.challenges.docker_binary'),
                imagePrefix: (string) config('faladev.challenges.image_prefix'),
                imageTag: (string) config('faladev.challenges.image_tag'),
                timeLimit: (int) config('faladev.challenges.time_limit'),
                memory: (string) config('faladev.challenges.memory'),
                cpus: (string) config('faladev.challenges.cpus'),
                maxOutputBytes: (int) config('faladev.challenges.max_output_bytes'),
            ),
            'disabled' => new DisabledCodeRunner,
            default => throw new InvalidArgumentException('Unknown code runner ['.config('faladev.challenges.runner').'].'),
        });
        $this->app->bind(ChallengeJudge::class, fn ($app) => new ChallengeJudge(
            runner: $app->make(CodeRunner::class),
            maxConcurrent: (int) config('faladev.challenges.max_concurrent'),
            timeLimit: (int) config('faladev.challenges.time_limit'),
        ));

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

        // Testing on a phone needs https (the browser only allows the
        // microphone on secure pages), which a tunnel such as Cloudflare
        // Tunnel provides. Trusting its forwarded headers makes Laravel build
        // https links. Local only: in production this would let any client
        // spoof the scheme and host.
        if ($this->app->isLocal()) {
            TrustProxies::at('*');
        }

        Gate::define('view-pilot-metrics', fn (User $user) => in_array(
            strtolower($user->email),
            config('faladev.pilot.admins'),
            true,
        ));
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
