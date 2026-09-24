<?php

namespace App\Http\Requests\Policies;

use Illuminate\Foundation\Http\FormRequest;

class StorePolicyVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
        ];
    }
}
