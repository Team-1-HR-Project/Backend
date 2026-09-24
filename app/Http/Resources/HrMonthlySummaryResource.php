<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrMonthlySummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalWorkedHours = number_format(($this->resource['total_worked_seconds'] ?? 0) / 3600, 1).'h';
        $user = $this->resource['user'];

        return [
            'user_id' => $user->id,
            'employee_code' => $user->employee_id,
            'name' => $user->name,
            'role' => $user->role,
            'job_title' => $user->job_title,
            'department' => $user->department?->name ?? 'N/A',
            'summary' => [
                'present_days' => $this->resource['present_days'],
                'late_days' => $this->resource['late_days'],
                'late_minutes_total' => $this->resource['late_minutes_total'],
                'absent_days' => $this->resource['absent_days'],
                'total_worked_hours' => $totalWorkedHours,
                'total_worked_seconds' => $this->resource['total_worked_seconds'],
            ],
        ];
    }
}
