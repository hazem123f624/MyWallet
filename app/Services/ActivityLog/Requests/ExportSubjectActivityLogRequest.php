<?php

namespace App\Services\ActivityLog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportSubjectActivityLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('subject_id')) {
            $this->merge(['subject_id' => $this->route('subject_id')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer'],
        ];
    }
}
