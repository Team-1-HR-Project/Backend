<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetManagerTeamAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->user()?->employee !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:Present,Late,Absent,On Shift'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
