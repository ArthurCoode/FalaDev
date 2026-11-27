export type InterviewStatus =
    | 'draft'
    | 'ready'
    | 'asking'
    | 'recording'
    | 'transcribing'
    | 'reviewing_answer'
    | 'processing_answer'
    | 'completed'
    | 'evaluating'
    | 'report_ready'
    | 'failed_retryable';

export type InterviewSessionSummary = {
    id: number;
    category: string;
    level: string;
    duration: string;
    status: InterviewStatus;
    statusLabel: string;
    createdAt: string | null;
};

export type UsageSummary = {
    used: number;
    limit: number;
    remaining: number;
};

export type InterviewOption = {
    value: string | number;
    label: string;
    description?: string;
};

export type InterviewSetupOptions = {
    categories: InterviewOption[];
    levels: InterviewOption[];
    durations: InterviewOption[];
};

export type InterviewSetupDraft = {
    category: string;
    level: string;
    duration_minutes: number;
};

export type InterviewSessionDetails = InterviewSessionSummary & {
    canEditSetup: boolean;
};

export type InterviewTurnDetails = {
    id: number;
    position: number;
    question: string;
    /** Pre-recorded audio of the question; null when only the text exists. */
    audioUrl: string | null;
    isFollowUp: boolean;
    answer: string | null;
};

/** A fixed interviewer line (opening, closing) and its recording. */
export type InterviewerLine = {
    text: string;
    audioUrl: string | null;
};

/** Laravel's paginator as Inertia receives it (only the fields we use). */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

/** The interview report (FalaDev.md, section 7). Scores go from 1 to 5. */
export type InterviewEvaluation = {
    /** Produced by the fake evaluator: scores do not judge the answers. */
    isSimulated: boolean;
    overallScore: number;
    summary: string;
    dimensions: Array<{
        key: string;
        label: string;
        score: number;
        explanation: string;
    }>;
    strengths: Array<{ point: string; evidence: string; position: number }>;
    improvements: Array<{
        point: string;
        evidence: string;
        position: number;
        action: string;
    }>;
    englishFeedback: Array<{
        original: string;
        suggestion: string;
        explanation: string;
        position: number;
    }>;
    nextSteps: string[];
    /** "Este feedback foi útil?", once answered. */
    userFeedback: { useful: boolean; comment: string | null } | null;
};
