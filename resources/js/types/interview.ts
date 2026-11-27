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
