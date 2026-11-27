import { onBeforeUnmount, ref } from 'vue';

export type PlaybackState =
    | 'idle'
    | 'loading'
    | 'playing'
    | 'played'
    | 'failed';

/**
 * Plays interviewer audio (pre-recorded files). Several URLs play one after
 * the other, e.g. the opening and then the first question. Missing URLs are
 * skipped; with none left the state is "failed", so the page falls back to
 * the text on screen (cursor/skills/FalaDev.md, section 8).
 *
 * When the browser blocks autoplay the state goes back to "idle" instead of
 * "failed": nothing is broken, the user only needs to click play.
 */
export function useAudioPlayer() {
    const state = ref<PlaybackState>('idle');

    let audio: HTMLAudioElement | null = null;
    // Each call gets a token, so a newer play/stop cancels the older sequence.
    let token = 0;

    function playOne(url: string, current: number): Promise<void> {
        return new Promise((resolve, reject) => {
            audio = new Audio(url);
            audio.onended = () => resolve();
            audio.onerror = () => reject(new Error('audio error'));
            audio
                .play()
                .then(() => {
                    if (current === token) {
                        state.value = 'playing';
                    }
                })
                .catch(reject);
        });
    }

    async function playSequence(urls: Array<string | null>): Promise<void> {
        stop();
        const current = ++token;
        const queue = urls.filter((url): url is string => Boolean(url));

        if (queue.length === 0) {
            state.value = 'failed';

            return;
        }

        state.value = 'loading';

        for (const url of queue) {
            try {
                await playOne(url, current);
            } catch (e) {
                if (current === token) {
                    state.value =
                        e instanceof DOMException &&
                        e.name === 'NotAllowedError'
                            ? 'idle'
                            : 'failed';
                }

                return;
            }

            if (current !== token) {
                return;
            }
        }

        state.value = 'played';
    }

    function stop(): void {
        token++;
        audio?.pause();
        audio = null;

        if (state.value === 'loading' || state.value === 'playing') {
            state.value = 'idle';
        }
    }

    onBeforeUnmount(stop);

    return {
        state,
        play: (url: string | null) => playSequence([url]),
        playSequence,
        stop,
    };
}
