<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeHrFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_title' => ['sometimes', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'in:Full-time,Part-time,Contract'],
            'status' => ['sometimes', 'in:active,inactive'],
            'department_id' => ['nullable', 'exists:departments,id'],
            
        ];
    }
}
