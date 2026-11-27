<?php

namespace App\Http\Controllers;

use App\Enums\PilotEventType;
use App\Models\PilotEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Technical events reported by the browser (microphone test results and
 * errors). Only whitelisted types and fields are accepted.
 */
class PilotEventController extends Controller
{
    private const MICROPHONE_ERRORS = ['permission_denied', 'no_device', 'device_busy', 'insecure_context', 'unsupported', 'unknown'];

    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(PilotEventType::class)->only(PilotEventType::fromBrowser())],
            'interview_session_id' => ['nullable', 'integer'],
            'passed' => ['required_if:type,microphone_check', 'boolean'],
            'peak' => ['required_if:type,microphone_check', 'numeric', 'between:0,1'],
            'error' => ['required_if:type,microphone_error', Rule::in(self::MICROPHONE_ERRORS)],
        ]);

        $type = PilotEventType::from($validated['type']);

        // Only the user's own session can be referenced; anything else is dropped.
        $sessionId = isset($validated['interview_session_id'])
            ? $request->user()->interviewSessions()->whereKey($validated['interview_session_id'])->value('id')
            : null;

        PilotEvent::record($type, $request->user()->id, $sessionId, match ($type) {
            PilotEventType::MicrophoneCheck => [
                'passed' => (bool) $validated['passed'],
                'peak' => round((float) $validated['peak'], 3),
            ],
            default => ['error' => $validated['error']],
        });

        return response()->noContent();
    }
}
