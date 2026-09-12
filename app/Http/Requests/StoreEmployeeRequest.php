<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:HR,Manager,Employee'],
            'job_title' => ['required', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'employment_type' => ['required', 'in:Full-time,Part-time,Contract'],
            'start_date' => ['required', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => [
                'nullable',
                'exists:employees,id',
                function ($attribute, $value, $fail) {
                    $manager = Employee::with('user')->find($value);
                    if ($manager && ! $manager->user?->hasRole('Manager')) {
                        $fail('The selected user must have a Manager role.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'company_location_id' => ['nullable', 'exists:company_locations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'The email address is already in use.',
            'role.in' => 'The selected role is invalid. Allowed roles are: HR, Manager, Employee.',
            'employment_type.in' => 'The selected employment type is invalid. Allowed types are: Full-time, Part-time, Contract.',
            'manager_id.exists' => 'The selected manager does not exist.',
        ];
    }
}
