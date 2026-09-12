<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Console\Command;

class MarkDailyAbsencesCommand extends Command
{
    protected $signature = 'attendance:mark-absent {date?}';

    protected $description = 'Automatically mark active employees without check-in as Absent for the target date';

    public function handle(): void
    {
        $targetDate = $this->argument('date') ?? now()->toDateString();

        $activeEmployees = Employee::where('status', 'active')->get();

        $absentCount = 0;

        foreach ($activeEmployees as $employee) {
            $hasAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $targetDate)
                ->exists();

            if (! $hasAttendance) {
                Attendance::create([
                    'employee_id' => $employee->id,
                    'date' => $targetDate,
                    'status' => 'Absent',
                    'company_location_id' => $employee->company_location_id,
                    'is_exception' => false,
                ]);

                $absentCount++;
            }
        }

        $this->info("Success: Marked {$absentCount} employees as Absent for date {$targetDate}.");
    }
}
