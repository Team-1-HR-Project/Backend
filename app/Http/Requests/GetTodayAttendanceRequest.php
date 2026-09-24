<?php

namespace App\Http\Requests;

use App\Services\GeofenceService;
use Illuminate\Foundation\Http\FormRequest;

class GetTodayAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => [
                'nullable', 
                'numeric', 
                'between:-180,180',
                
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.numeric' => __('validation.latitude_numeric'),
            'latitude.between' => __('validation.latitude_between'),
            'longitude.numeric' => __('validation.longitude_numeric'),
            'longitude.between' => __('validation.longitude_between'),
        ];
    }
}