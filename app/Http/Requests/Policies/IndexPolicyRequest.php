<?php

namespace App\Http\Requests\Policies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'active',
                    'archived',
                ]),
            ],

            'version_status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'active',
                    'archived',
                ]),
            ],
        ];
    }
}
