<?php

namespace App\Http\Requests\CompanyEvents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            if ($startDate && $endDate && $endDate < $startDate) {
                $validator->errors()->add(
                    'end_date',
                    'The end date must be after or equal to the start date.'
                );
            }
        });
    }
}
