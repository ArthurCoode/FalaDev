/** Study track: see app/Study and resources/study/curriculum.php. */

export type StudyStats = {
    streak: number;
    longestStreak: number;
    studiedToday: boolean;
    freezes: number;
    maxFreezes: number;
    freezeEveryDays: number;
    totalXp: number;
    reviewsDue: number;
    /** Phrases in the review queue, not mastered yet. */
    learning: number;
    mastered: number;
};

export type StudyLessonStatus = 'completed' | 'next' | 'available' | 'locked';

export type StudyLessonSummary = {
    slug: string;
    title: string;
    description: string;
    exercises: number;
    bestCorrect: number | null;
    status: StudyLessonStatus;
};

export type StudyUnit = {
    slug: string;
    title: string;
    description: string;
    completedCount: number;
    lessons: StudyLessonSummary[];
};

export type StudyLessonLink = { slug: string; title: string };

type ExerciseBase = {
    key: string;
    prompt: string;
    explain: string;
};

/** Pick an option; for "gap" the option fills the "___" of the sentence. */
export type ChoiceExercise = ExerciseBase & {
    type: 'choice' | 'gap';
    sentence: string | null;
    options: string[];
    answer: string;
};

/** Build the sentence from shuffled words (some do not belong). */
export type OrderExercise = ExerciseBase & {
    type: 'order';
    sentence: string;
    tokens: string[];
    /** Accepted word sequences, lower case. */
    solutions: string[][];
};

export type StudyExercise = ChoiceExercise | OrderExercise;

/** Answer sent to the server: the option text, or the words in order. */
export type StudyAnswer = string | string[];

export type StudyOutcome = {
    correct: number;
    total: number;
    xp: number;
    totalXp: number;
    streak: number;
    longestStreak: number;
    streakExtended: boolean;
    freezesUsed: number;
    freezeEarned: boolean;
    freezes: number;
    sentToReview: number;
    mastered: number;
    nextLesson: StudyLessonLink | null;
    reviewsDue: number;
};
