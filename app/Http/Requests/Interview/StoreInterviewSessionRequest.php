<?php

namespace App\Http\Requests\Interview;

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInterviewSessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(InterviewCategory::class)],
            'level' => ['required', Rule::enum(InterviewLevel::class)],
            'duration_minutes' => ['required', 'integer', Rule::enum(InterviewDuration::class)],
            'data_notice_accepted' => ['accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Escolha uma categoria.',
            'level.required' => 'Escolha um nível.',
            'duration_minutes.required' => 'Escolha uma duração.',
            'data_notice_accepted.accepted' => 'Confirme que leu como seus dados são usados.',
        ];
    }
}
