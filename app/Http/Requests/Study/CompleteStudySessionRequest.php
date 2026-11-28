<?php

namespace App\Http\Requests\Study;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The first-try answers of a finished lesson or review. Answers are a list of
 * {key, value} because exercise keys contain dots, which validation would
 * read as nesting.
 */
class CompleteStudySessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'attempt' => ['required', 'uuid'],
            'answers' => ['required', 'array', 'min:1', 'max:50'],
            'answers.*' => ['required', 'array:key,value'],
            'answers.*.key' => ['required', 'string', 'max:120', 'distinct'],
            'answers.*.value' => ['present'],
        ];
    }

    /**
     * Answers keyed by exercise key.
     *
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        $answers = [];

        foreach ($this->validated('answers') as $answer) {
            $answers[(string) $answer['key']] = $answer['value'];
        }

        return $answers;
    }
}
