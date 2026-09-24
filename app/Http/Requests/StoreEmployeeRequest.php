<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => [
                'required',
                'string',
                'in:HR,Manager,Employee',
                function ($attribute, $value, $fail) {
                    if ($value === 'HR' && ! $this->user()?->hasRole('Owner')) {
                        $fail(__('employees.only_owner_can_hr'));
                    }
                },
            ],
            'job_title' => ['required', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'employment_type' => ['required', 'in:Full-time,Part-time,Contract'],
            'start_date' => ['required', 'date'],
            'department_id' => [
                'nullable',
                'required_if:role,Employee,Manager',
                'exists:departments,id',
            ],

            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'address' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('validation.unique', ['attribute' => __('validation.attributes.email')]),
            'role.in' => __('validation.in', ['attribute' => __('validation.attributes.role')]),
            'employment_type.in' => __('validation.in', ['attribute' => __('validation.attributes.employment_type')]),
            'department_id.required_if' => __('validation.required', ['attribute' => __('validation.attributes.department')]),
        ];
    }
}
