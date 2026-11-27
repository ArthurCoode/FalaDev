<?php

namespace App\Http\Controllers;

use App\Enums\InterviewStatus;
use App\Interview\InterviewFlow;
use App\Models\InterviewTurn;
use App\Speech\Contracts\SpeechToText;
use App\Speech\Exceptions\TranscriptionFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Actions on the current question: transcribe a recording, send the
 * reviewed answer and repeat the question.
 */
class InterviewTurnController extends Controller
{
    /**
     * Transcribes one recording and returns the text for review. Nothing is
     * saved: the upload is a temporary file that PHP deletes after the
     * request, and the answer only counts once the user sends it.
     */
    public function transcribe(Request $request, int $interviewSession, int $turn, SpeechToText $stt): JsonResponse
    {
        $turn = $this->findOwnTurn($request, $interviewSession, $turn);

        if ($turn->session->status !== InterviewStatus::Asking || $turn->isAnswered()) {
            return response()->json(['message' => 'Esta pergunta já foi respondida.'], 409);
        }

        $validated = $request->validate([
            'audio' => [
                'required',
                'file',
                'max:'.config('faladev.interview.max_answer_upload_kb'),
                // Browsers record WebM/Ogg (Chrome, Firefox) or MP4 (Safari);
                // an audio-only WebM is often detected as video/webm.
                'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,video/mp4,audio/mpeg,audio/wav,audio/x-wav',
            ],
        ], [
            'audio.max' => 'A gravação ficou grande demais. Grave uma resposta mais curta.',
            'audio.mimetypes' => 'Formato de áudio não suportado por este navegador.',
        ]);

        $file = $validated['audio'];
        $startedAt = microtime(true);

        try {
            $transcript = $stt->transcribe($file->getRealPath(), (string) $file->getMimeType());
        } catch (TranscriptionFailed $e) {
            report($e);

            return response()->json(['message' => 'Não foi possível transcrever agora.'], 503);
        }

        // Operational metadata only (FalaDev.md, section 10): never audio or text.
        $turn->metadata = [
            ...($turn->metadata ?? []),
            'transcriptions' => (($turn->metadata['transcriptions'] ?? 0) + 1),
            'last_transcription_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
        $turn->save();

        return response()->json(['transcript' => $transcript]);
    }

    /**
     * Sends the reviewed transcript. Sending the same turn twice is harmless.
     */
    public function answer(Request $request, int $interviewSession, int $turn, InterviewFlow $flow): RedirectResponse
    {
        $turn = $this->findOwnTurn($request, $interviewSession, $turn);

        $validated = $request->validate([
            'transcript' => ['required', 'string', 'max:5000'],
        ], [
            'transcript.required' => 'Escreva ou grave sua resposta antes de enviar.',
        ]);

        $flow->submitAnswer($turn->session, $turn, trim($validated['transcript']));

        return to_route('interviews.show', $interviewSession);
    }

    public function repeat(Request $request, int $interviewSession, int $turn, InterviewFlow $flow): Response
    {
        $turn = $this->findOwnTurn($request, $interviewSession, $turn);
        $flow->repeatQuestion($turn->session, $turn);

        return response()->noContent();
    }

    /**
     * Other users' sessions and turns respond 404.
     */
    private function findOwnTurn(Request $request, int $sessionId, int $turnId): InterviewTurn
    {
        $session = $request->user()->interviewSessions()->findOrFail($sessionId);
        $turn = $session->turns()->findOrFail($turnId);
        $turn->setRelation('session', $session);

        return $turn;
    }
}
