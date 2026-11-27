<?php

namespace App\Evaluation\Contracts;

use App\Evaluation\EvaluationResult;
use App\Evaluation\Exceptions\EvaluationFailed;
use App\Models\InterviewSession;

/**
 * Turns the answered turns of an interview into a report. Kept separate from
 * the interviewer (cursor/skills/FalaDev.md, section 14, item 7).
 */
interface Evaluator
{
    /**
     * Short name stored with the report ("fake", later the model used).
     */
    public function name(): string;

    /**
     * @throws EvaluationFailed
     */
    public function evaluate(InterviewSession $session): EvaluationResult;
}
