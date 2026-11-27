<?php

namespace App\Enums;

/**
 * Technical events counted in the pilot metrics (FalaDev.md, section 12).
 */
enum PilotEventType: string
{
    /** Pre-interview microphone test finished: data = {passed, peak}. */
    case MicrophoneCheck = 'microphone_check';

    /** The browser could not use the microphone: data = {error}. */
    case MicrophoneError = 'microphone_error';

    /** The STT provider failed on an answer. */
    case TranscriptionFailed = 'transcription_failed';

    /** The report could not be produced. */
    case EvaluationFailed = 'evaluation_failed';

    /**
     * Types the browser may send; the others are only recorded by the server.
     *
     * @return list<self>
     */
    public static function fromBrowser(): array
    {
        return [self::MicrophoneCheck, self::MicrophoneError];
    }
}
