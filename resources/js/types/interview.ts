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
};
