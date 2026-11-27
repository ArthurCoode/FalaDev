<?php

namespace App\Interview\Contracts;

use App\Interview\NextQuestion;
use App\Models\InterviewSession;

/**
 * Decides what the interviewer asks next. Kept separate from the evaluator
 * (cursor/skills/FalaDev.md, section 14, item 7).
 */
interface Interviewer
{
    /**
     * The next question given the turns so far, or null when the interview
     * has covered what it planned to ask.
     */
    public function nextQuestion(InterviewSession $session): ?NextQuestion;

    /**
     * Every line this interviewer can say that never changes, so it can be
     * pre-recorded. An LLM-based interviewer returns an empty list.
     *
     * @return list<string>
     */
    public function fixedLines(): array;
}
