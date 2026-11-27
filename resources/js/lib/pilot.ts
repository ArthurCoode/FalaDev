import { post } from '@/lib/http';
import type { RecorderError } from '@/composables/useAudioRecorder';
import { store as storePilotEvent } from '@/routes/pilot/events';

type PilotEvent =
    | { type: 'microphone_check'; passed: boolean; peak: number }
    | { type: 'microphone_error'; error: RecorderError };

/**
 * Sends a technical event for the pilot metrics (FalaDev.md, section 12):
 * only numbers and error codes, never audio or text. Fire and forget: a
 * failure here must never affect the user.
 */
export function reportPilotEvent(
    event: PilotEvent,
    interviewSessionId?: number,
): void {
    const body = new FormData();

    for (const [key, value] of Object.entries(event)) {
        body.append(
            key,
            typeof value === 'boolean' ? (value ? '1' : '0') : String(value),
        );
    }

    if (interviewSessionId) {
        body.append('interview_session_id', String(interviewSessionId));
    }

    void post(storePilotEvent.url(), body).catch(() => undefined);
}
