<?php

namespace App\Exports;

use App\Services\AttendanceService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HrMonthlySummaryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private AttendanceService $attendanceService,
        private int $month,
        private int $year,
        private ?int $departmentId = null
    ) {}

    public function collection(): Collection
    {
        return $this->attendanceService->getHrMonthlySummaryAll(
            $this->month,
            $this->year,
            $this->departmentId
        );
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
            'Job Title',
            'Department',
            'Present Days',
            'Late Days',
            'Total Late Minutes',
            'Absent Days',
            'Total Worked Hours',
        ];
    }

    public function map($row): array
    {
        $totalWorkedHours = number_format(($row['total_worked_seconds'] ?? 0) / 3600, 1).'h';

        return [
            $row['employee']->employee_id,
            $row['employee']->user?->name,
            $row['employee']->job_title,
            $row['employee']->department?->name ?? 'N/A',
            $row['present_days'],
            $row['late_days'],
            $row['late_minutes_total'],
            $row['absent_days'],
            $totalWorkedHours,
        ];
    }
}
