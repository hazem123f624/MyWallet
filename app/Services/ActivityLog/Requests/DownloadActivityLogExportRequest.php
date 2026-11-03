<?php

namespace App\Services\ActivityLog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DownloadActivityLogExportRequest extends FormRequest
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
        if ($this->route('fileName')) {
            $this->merge(['file_name' => $this->route('fileName')]);
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
            'file_name' => ['required', 'string', 'regex:/^activity-log-export-[0-9]+-\d{8}_\d{6}\.csv$/'],
        ];
    }
}
