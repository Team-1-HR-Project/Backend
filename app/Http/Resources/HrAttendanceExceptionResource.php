<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrAttendanceExceptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'attendance_id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'employee' => [
                'id' => $this->employee?->id,
                'name' => $this->employee?->user?->name,
                'employee_code' => $this->employee?->employee_id,
                'job_title' => $this->employee?->job_title,
                'department' => $this->employee?->department?->name ?? 'N/A',
            ],
            'check_in' => $this->check_in?->format('h:i A'),
            'check_out' => $this->check_out?->format('h:i A'),
            'status' => $this->status,
            'exception_reason' => $this->exception_reason ?? 'Unusual check-in pattern or out of shift hours',
            'location_name' => $this->companyLocation?->name,
        ];
    }
}
