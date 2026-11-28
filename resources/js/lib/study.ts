import type { StudyAnswer, StudyExercise } from '@/types';

/**
 * Same rules as App\Study\Exercise::isCorrect(), for instant feedback. The
 * server grades again when the lesson is finished; its result is the one
 * that counts.
 */
export function isCorrect(
    exercise: StudyExercise,
    answer: StudyAnswer,
): boolean {
    if (exercise.type === 'order') {
        if (!Array.isArray(answer)) {
            return false;
        }

        const words = normalizeWords(answer);

        return exercise.solutions.some(
            (solution) =>
                solution.length === words.length &&
                solution.every((word, index) => word === words[index]),
        );
    }

    return answer === exercise.answer;
}

/** Lower case and straight apostrophes, so "I’m" matches "i'm". */
export function normalizeWords(words: string[]): string[] {
    return words.map((word) => word.trim().replace(/’/g, "'").toLowerCase());
}

/** Fisher-Yates, returning a new array. */
export function shuffled<T>(items: readonly T[]): T[] {
    const copy = [...items];

    for (let i = copy.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [copy[i], copy[j]] = [copy[j], copy[i]];
    }

    return copy;
}

/**
 * Identifies one try at a lesson, so finishing it twice (a retry after a
 * network error) counts once. crypto.randomUUID only exists on secure pages,
 * hence the fallback for plain http on a local network.
 */
export function newAttemptId(): string {
    // randomUUID is typed as always present, but browsers drop it on http.
    if (typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((byte) => byte.toString(16).padStart(2, '0'));

    return `${hex.slice(0, 4).join('')}-${hex.slice(4, 6).join('')}-${hex.slice(6, 8).join('')}-${hex.slice(8, 10).join('')}-${hex.slice(10).join('')}`;
}
