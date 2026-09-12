<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAttendanceHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'digits:4', 'min:2020'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
