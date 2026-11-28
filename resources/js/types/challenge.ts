/** Code challenges: see app/Challenges and resources/challenges/{language}.php. */

export type ChallengeDifficulty = 'easy' | 'medium' | 'hard';

/** App\Enums\ChallengeLanguage */
export type ChallengeLanguage =
    | 'javascript'
    | 'typescript'
    | 'python'
    | 'java'
    | 'csharp'
    | 'go'
    | 'php';

export type ChallengeLanguageSummary = {
    value: ChallengeLanguage;
    label: string;
    total: number;
    solved: number;
};

export type ChallengeSummary = {
    slug: string;
    language: ChallengeLanguage;
    title: string;
    difficulty: ChallengeDifficulty;
    summary: string;
    /** Glossary terms in the statement. */
    terms: number;
    solved: boolean;
};

export type ChallengeTest = {
    /** The call as the user writes it: "two_sum([2, 7], 9)". */
    call: string;
    /** The expected value, as JSON. */
    expected: string;
};

export type ChallengeDetails = {
    slug: string;
    language: ChallengeLanguage;
    languageLabel: string;
    title: string;
    difficulty: ChallengeDifficulty;
    summary: string;
    /** The declaration in this language, shown above the editor. */
    signature: string;
    /** Paragraphs in English; glossary terms marked as {{term}}, code as `code`. */
    statement: string[];
    examples: { input: string; output: string; explanation?: string }[];
    constraints: string[];
    glossary: { term: string; meaning: string }[];
    hints: string[];
    starter: string;
    tests: ChallengeTest[];
    explanation: { en: string; pt: string };
};

export type ChallengeSolveOutcome = {
    firstSolve: boolean;
    xp: number;
    totalXp: number;
    streak: number;
    streakExtended: boolean;
};

export type ChallengeTestResult = {
    index: number;
    passed: boolean;
    /** What the function returned, as JSON. Absent when it threw. */
    actual?: string;
    error?: string;
};

/** App\Challenges\Running\JudgeResult, plus the solve when every test passed. */
export type ChallengeRunResult = {
    status: 'ok' | 'error' | 'timeout' | 'busy' | 'unavailable';
    results: ChallengeTestResult[];
    /** What the program printed. */
    logs: string[];
    error: string | null;
    seconds: number;
    outcome: ChallengeSolveOutcome | null;
};
