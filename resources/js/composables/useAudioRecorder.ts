import { computed, onBeforeUnmount, ref, shallowRef } from 'vue';

export type RecorderState =
    | 'idle'
    | 'requesting'
    | 'recording'
    | 'recorded'
    | 'error';

export type RecorderError =
    | 'unsupported'
    | 'insecure_context'
    | 'permission_denied'
    | 'no_device'
    | 'device_busy'
    | 'unknown';

type Options = {
    /** Recording stops by itself after this long. */
    maxDurationMs?: number;
};

/**
 * Microphone recording in the browser: permission, live input level,
 * stop/cancel and local playback. The audio stays in memory as a Blob;
 * uploading it is the caller's decision.
 *
 * The microphone is only requested when start() is called (privacy rule,
 * cursor/skills/FalaDev.md section 9) and released as soon as recording ends.
 */
export function useAudioRecorder(options: Options = {}) {
    const maxDurationMs = options.maxDurationMs ?? 60_000;

    const state = ref<RecorderState>('idle');
    const error = ref<RecorderError | null>(null);
    const level = ref(0);
    const peakLevel = ref(0);
    const elapsedMs = ref(0);
    const audioBlob = shallowRef<Blob | null>(null);
    const audioUrl = ref<string | null>(null);

    let stream: MediaStream | null = null;
    let recorder: MediaRecorder | null = null;
    let audioContext: AudioContext | null = null;
    let frame = 0;
    let startedAt = 0;
    let discard = false;
    let chunks: Blob[] = [];

    const isRecording = computed(() => state.value === 'recording');

    function releaseDevices(): void {
        cancelAnimationFrame(frame);
        stream?.getTracks().forEach((track) => track.stop());
        void audioContext?.close();
        stream = null;
        audioContext = null;
        level.value = 0;
    }

    function clearRecording(): void {
        if (audioUrl.value) {
            URL.revokeObjectURL(audioUrl.value);
        }

        audioUrl.value = null;
        audioBlob.value = null;
    }

    function toRecorderError(e: unknown): RecorderError {
        const name = e instanceof DOMException ? e.name : '';

        switch (name) {
            case 'NotAllowedError':
            case 'SecurityError':
                return 'permission_denied';
            case 'NotFoundError':
            case 'OverconstrainedError':
                return 'no_device';
            case 'NotReadableError':
            case 'AbortError':
                return 'device_busy';
            default:
                return 'unknown';
        }
    }

    function trackLevel(source: MediaStream): void {
        audioContext = new AudioContext();
        const analyser = audioContext.createAnalyser();
        analyser.fftSize = 1024;
        audioContext.createMediaStreamSource(source).connect(analyser);
        const samples = new Float32Array(analyser.fftSize);

        const tick = () => {
            analyser.getFloatTimeDomainData(samples);
            let sum = 0;

            for (const sample of samples) {
                sum += sample * sample;
            }

            // RMS scaled so normal speech lands roughly between 0.3 and 1.
            const rms = Math.min(Math.sqrt(sum / samples.length) * 4, 1);
            level.value = rms;
            peakLevel.value = Math.max(peakLevel.value, rms);
            elapsedMs.value = performance.now() - startedAt;

            if (elapsedMs.value >= maxDurationMs) {
                stop();

                return;
            }

            frame = requestAnimationFrame(tick);
        };

        tick();
    }

    async function start(): Promise<void> {
        if (state.value === 'requesting' || state.value === 'recording') {
            return;
        }

        clearRecording();
        error.value = null;
        peakLevel.value = 0;
        elapsedMs.value = 0;

        if (!window.isSecureContext) {
            state.value = 'error';
            error.value = 'insecure_context';

            return;
        }

        if (
            !navigator.mediaDevices?.getUserMedia ||
            typeof MediaRecorder === 'undefined'
        ) {
            state.value = 'error';
            error.value = 'unsupported';

            return;
        }

        state.value = 'requesting';

        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (e) {
            state.value = 'error';
            error.value = toRecorderError(e);

            return;
        }

        chunks = [];
        discard = false;
        recorder = new MediaRecorder(stream);
        recorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                chunks.push(event.data);
            }
        };
        recorder.onstop = () => {
            releaseDevices();

            if (discard) {
                state.value = 'idle';

                return;
            }

            const blob = new Blob(chunks, {
                type: recorder?.mimeType || 'audio/webm',
            });
            audioBlob.value = blob;
            audioUrl.value = URL.createObjectURL(blob);
            state.value = 'recorded';
        };

        startedAt = performance.now();
        recorder.start();
        state.value = 'recording';
        trackLevel(stream);
    }

    function stop(): void {
        if (recorder?.state === 'recording') {
            recorder.stop();
        }
    }

    function cancel(): void {
        discard = true;
        stop();
    }

    function reset(): void {
        cancel();
        clearRecording();
        error.value = null;
        state.value = 'idle';
    }

    onBeforeUnmount(() => {
        discard = true;

        if (recorder?.state === 'recording') {
            recorder.stop();
        }

        releaseDevices();
        clearRecording();
    });

    return {
        state,
        error,
        level,
        peakLevel,
        elapsedMs,
        audioBlob,
        audioUrl,
        isRecording,
        start,
        stop,
        cancel,
        reset,
    };
}
