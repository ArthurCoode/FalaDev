<?php

namespace App\Http\Controllers;

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewLevel;
use App\Enums\InterviewStatus;
use App\Http\Requests\Interview\StoreInterviewSessionRequest;
use App\Models\InterviewSession;
use App\Services\InterviewUsage;
use App\Speech\Contracts\TextToSpeech;
use App\Speech\Exceptions\SpeechSynthesisFailed;
use App\Speech\SynthesizedSpeech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InterviewSessionController extends Controller
{
    /**
     * Interview setup: category, level, duration and the data notice.
     */
    public function create(Request $request, InterviewUsage $usage): Response
    {
        $draft = $this->currentOpenSession($request);

        return Inertia::render('interviews/Create', [
            'options' => [
                'categories' => array_map(fn (InterviewCategory $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'description' => $case->description(),
                ], InterviewCategory::cases()),
                'levels' => array_map(fn (InterviewLevel $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], InterviewLevel::cases()),
                'durations' => array_map(fn (InterviewDuration $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'description' => $case->description(),
                ], InterviewDuration::cases()),
            ],
            'draft' => $draft ? [
                'category' => $draft->category->value,
                'level' => $draft->level->value,
                'duration_minutes' => $draft->duration_minutes->value,
            ] : null,
            'usage' => $usage->summary($request->user()),
        ]);
    }

    /**
     * Save the setup as the user's single draft session.
     */
    public function store(StoreInterviewSessionRequest $request, InterviewUsage $usage): RedirectResponse
    {
        if ($usage->remaining($request->user()) === 0) {
            throw ValidationException::withMessages([
                'usage' => 'Você atingiu o limite de entrevistas deste mês.',
            ]);
        }

        // Reusing the open session makes repeated submits (double click, back
        // and resubmit) update one session instead of creating duplicates.
        $session = $this->currentOpenSession($request) ?? new InterviewSession;
        $session->fill($request->safe()->only(['category', 'level', 'duration_minutes']));

        // A changed setup must go through the microphone and audio checks again.
        if ($session->status === InterviewStatus::Ready) {
            $session->transitionTo(InterviewStatus::Draft);
        }

        $request->user()->interviewSessions()->save($session);

        return to_route('interviews.show', $session);
    }

    /**
     * Session overview with the pre-interview checks.
     */
    public function show(Request $request, int $interviewSession): Response
    {
        $session = $this->findOwnSession($request, $interviewSession);

        return Inertia::render('interviews/Show', [
            'session' => [
                'id' => $session->id,
                'category' => $session->category->label(),
                'level' => $session->level->label(),
                'duration' => $session->duration_minutes->label(),
                'status' => $session->status->value,
                'statusLabel' => $session->status->label(),
                'createdAt' => $session->created_at?->toIso8601String(),
                'canEditSetup' => $session->isOpenForSetup(),
            ],
            'samplePhrase' => config('faladev.speech.sample_phrase'),
            // The fake adapter plays a chime, not the phrase; the page says so.
            'ttsIsFake' => config('faladev.speech.tts') === 'fake',
        ]);
    }

    /**
     * Audio for the pre-interview sound check, produced by the TTS adapter.
     */
    public function sampleAudio(Request $request, int $interviewSession, TextToSpeech $tts): HttpResponse|JsonResponse
    {
        $this->findOwnSession($request, $interviewSession);

        try {
            $speech = $this->cachedSamplePhrase($tts);
        } catch (SpeechSynthesisFailed $e) {
            report($e);

            return response()->json(['message' => 'Não foi possível gerar o áudio agora.'], 503);
        }

        return response($speech->audio, 200, [
            'Content-Type' => $speech->mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * The sample phrase never changes, so it is synthesized once and reused:
     * each provider call costs characters from the TTS quota. The key covers
     * every setting that changes the audio. Failures are not cached.
     */
    private function cachedSamplePhrase(TextToSpeech $tts): SynthesizedSpeech
    {
        $phrase = config('faladev.speech.sample_phrase');
        $key = 'speech-sample:'.sha1(json_encode([
            config('faladev.speech.tts'),
            config('services.elevenlabs.voice_id'),
            config('services.elevenlabs.model_id'),
            config('services.elevenlabs.output_format'),
            $phrase,
        ]));

        // Base64 keeps binary audio safe in text-based cache stores (database).
        $cached = Cache::remember($key, now()->addDays(30), function () use ($tts, $phrase) {
            $speech = $tts->synthesize($phrase);

            return ['audio' => base64_encode($speech->audio), 'mimeType' => $speech->mimeType];
        });

        return new SynthesizedSpeech(base64_decode($cached['audio']), $cached['mimeType']);
    }

    /**
     * The user finished the microphone and audio checks.
     */
    public function markReady(Request $request, int $interviewSession): RedirectResponse
    {
        $session = $this->findOwnSession($request, $interviewSession);

        // Idempotent: a repeated click must not fail.
        if ($session->status !== InterviewStatus::Ready) {
            if (! $session->status->canTransitionTo(InterviewStatus::Ready)) {
                throw ValidationException::withMessages([
                    'status' => 'Esta entrevista não está mais em preparação.',
                ]);
            }

            $session->transitionTo(InterviewStatus::Ready);
            $session->save();
        }

        return to_route('interviews.show', $session);
    }

    /**
     * Other users' sessions respond 404, not 403, so their existence is not revealed.
     */
    private function findOwnSession(Request $request, int $id): InterviewSession
    {
        return $request->user()->interviewSessions()->findOrFail($id);
    }

    private function currentOpenSession(Request $request): ?InterviewSession
    {
        return $request->user()->interviewSessions()
            ->whereIn('status', [InterviewStatus::Draft, InterviewStatus::Ready])
            ->latest('id')
            ->first();
    }
}
