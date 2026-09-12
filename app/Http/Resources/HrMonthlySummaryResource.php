<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrMonthlySummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalWorkedHours = number_format(($this->resource['total_worked_seconds'] ?? 0) / 3600, 1).'h';

        return [
            'employee_id' => $this->resource['employee']->id,
            'employee_code' => $this->resource['employee']->employee_id,
            'name' => $this->resource['employee']->user?->name,
            'job_title' => $this->resource['employee']->job_title,
            'department' => $this->resource['employee']->department?->name ?? 'N/A',
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
