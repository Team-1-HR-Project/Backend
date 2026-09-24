<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceService
{
    public function __construct(private GeofenceService $geofenceService) {}

    public function checkIn(User $user, float $lat, float $lng): Attendance
    {
        $today = now()->toDateString();

        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existingAttendance) {
            throw new Exception('DUPLICATE_CHECKIN');
        }

        $location = $user->companyLocation;
        if (! $location || ! $location->is_active) {
            throw new Exception('LOCATION_NOT_CONFIGURED');
        }

        $isInside = $this->geofenceService->isWithinRadius(
            $lat, $lng, $location->latitude, $location->longitude, $location->radius
        );

        if (! $isInside) {
            throw new Exception('OUTSIDE_RADIUS');
        }

        $gracePeriodEnd = Carbon::parse('09:15:00');
        $shiftEnd = Carbon::parse('17:00:00');

        $now = now();
        $isException = false;
        $exceptionReason = null;

        if ($now->lte($gracePeriodEnd)) {
            $status = 'Present';
        } elseif ($now->between($gracePeriodEnd, $shiftEnd)) {
            $status = 'Late';
        } else {
            $status = 'Late';
            $isException = true;
            $exceptionReason = 'Check-in recorded after official shift hours.';
        }

        return Attendance::create([
            'user_id' => $user->id,
            'company_location_id' => $location->id,
            'date' => $today,
            'check_in' => $now,
            'check_in_lat' => $lat,
            'check_in_lng' => $lng,
            'status' => $status,
            'is_exception' => $isException,
            'exception_reason' => $exceptionReason,
        ]);
    }

    public function checkOut(User $user): Attendance
    {
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (! $attendance || ! $attendance->check_in) {
            throw new Exception('NO_OPEN_CHECKIN');
        }

        if ($attendance->check_out) {
            throw new Exception('ALREADY_CHECKED_OUT');
        }

        $now = now();
        $workedSeconds = (int) abs($now->diffInSeconds($attendance->check_in));

        $attendance->update([
            'check_out' => $now,
            'worked_seconds' => $workedSeconds,
        ]);

        return $attendance;
    }

    public function getTodayData(User $user, ?float $currentLat = null, ?float $currentLng = null): array
    {
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->first();

        $location = $user->companyLocation;
        $distance = null;
        $isInside = false;

        if ($location && $currentLat && $currentLng) {
            $distance = $this->geofenceService->calculateDistance(
                $currentLat, $currentLng, $location->latitude, $location->longitude
            );
            $isInside = $distance <= $location->radius;
        }

        $workedSeconds = 0;
        if ($attendance && $attendance->check_in) {
            $endTime = $attendance->check_out ?? now();
            $workedSeconds = (int) abs($endTime->diffInSeconds($attendance->check_in));
        }

        return [
            'has_checked_in' => (bool) ($attendance?->check_in),
            'has_checked_out' => (bool) ($attendance?->check_out),
            'check_in_time' => $attendance?->check_in?->format('h:i A'),
            'check_out_time' => $attendance?->check_out?->format('h:i A'),
            'status' => $attendance?->status ?? 'Absent',
            'worked_seconds' => $workedSeconds,
            'distance_meters' => $distance,
            'is_inside_radius' => $isInside,
        ];
    }

    public function getHistory(User $user, ?int $month = null, ?int $year = null, int $perPage = 15): LengthAwarePaginator
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        return Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    public function getManagerTeamTodayData(User $manager, ?string $date = null, ?string $statusFilter = null, ?string $search = null, int $perPage = 15): array
    {
        $targetDate = $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
        $formattedDate = $targetDate->toDateString();
        $todayDate = now()->startOfDay();

        $subordinateIds = User::where('manager_id', $manager->id)
            ->where('status', 'active')
            ->pluck('id');

        $totalTeamCount = $subordinateIds->count();
        $todayAttendances = Attendance::whereIn('user_id', $subordinateIds)
            ->where('date', $formattedDate)
            ->get();

        $presentCount = $todayAttendances->where('status', 'Present')->count();
        $lateCount = $todayAttendances->where('status', 'Late')->count();
        $checkedInCount = $todayAttendances->whereNotNull('check_in')->count();

        $absentCount = $targetDate->gt($todayDate) 
            ? 0 
            : max(0, $totalTeamCount - $checkedInCount);

        $onShiftCount = $todayAttendances->whereNotNull('check_in')->whereNull('check_out')->count();

        $totalSecondsWorked = $todayAttendances->sum('worked_seconds');
        $completedShiftsCount = $todayAttendances->whereNotNull('worked_seconds')->where('worked_seconds', '>', 0)->count();
        $avgHours = $completedShiftsCount > 0 ? number_format(($totalSecondsWorked / $completedShiftsCount) / 3600, 1).'h' : '0.0h';

        $startOfWeek = $targetDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $targetDate->copy()->endOfWeek(Carbon::SUNDAY);

        $weeklyAttendances = Attendance::whereIn('user_id', $subordinateIds)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        $weeklyChart = [];
        for ($day = $startOfWeek->copy(); $day->lte($endOfWeek); $day->addDay()) {
            $dayDate = $day->toDateString();
            $isFutureDay = $day->gt($todayDate); 

            $dayAtts = $weeklyAttendances->where('date', $dayDate);

            $weeklyChart[] = [
                'day' => $day->format('D'),
                'date' => $dayDate,
                'present' => $dayAtts->where('status', 'Present')->count(),
                'late' => $dayAtts->where('status', 'Late')->count(),
                'absent' => $isFutureDay 
                    ? 0 
                    : max(0, $totalTeamCount - $dayAtts->whereNotNull('check_in')->count()),
            ];
        }

        $query = User::with(['todayAttendance' => fn ($q) => $q->where('date', $formattedDate)])
            ->where('manager_id', $manager->id)
            ->where('status', 'active');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($statusFilter) {
            if ($statusFilter === 'Absent') {
                $query->whereDoesntHave('todayAttendance', fn ($q) => $q->where('date', $formattedDate));
            } elseif ($statusFilter === 'On Shift') {
                $query->whereHas('todayAttendance', fn ($q) => $q->where('date', $formattedDate)->whereNotNull('check_in')->whereNull('check_out'));
            } else {
                $query->whereHas('todayAttendance', fn ($q) => $q->where('date', $formattedDate)->where('status', $statusFilter));
            }
        }

        $paginatedTeam = $query->paginate($perPage);

        return [
            'selected_date' => $formattedDate,
            'summary' => [
                'present' => $presentCount,
                'late' => $lateCount,
                'absent' => $absentCount,
                'avg_hours' => $avgHours,
                'on_shift' => $onShiftCount,
                'total_team' => $totalTeamCount,
            ],
            'weekly_chart' => $weeklyChart,
            'team' => $paginatedTeam,
        ];
    }

    public function getManagerEmployeeAttendanceDetail(User $manager, int $employeeId, ?string $date = null): ?array
    {
        $formattedDate = $date ?? now()->toDateString();

        $employee = User::with(['companyLocation'])
            ->where('id', $employeeId)
            ->where('manager_id', $manager->id)
            ->first();

        if (! $employee) {
            return null;
        }

        $attendance = Attendance::where('user_id', $employee->id)
            ->where('date', $formattedDate)
            ->first();

        return [
            'employee' => $employee,
            'attendance' => $attendance,
            'date' => $formattedDate,
        ];
    }

    public function getHrDailyAttendance(?string $date = null, ?int $departmentId = null, ?int $managerId = null, ?string $statusFilter = null, ?string $search = null, int $perPage = 15): array
    {
        $formattedDate = $date ?? now()->toDateString();

        $query = User::query()
            ->excludeOwnerAndSelf()
            ->with(['department', 'manager', 'todayAttendance' => function ($q) use ($formattedDate) {
                $q->where('date', $formattedDate);
            }])
            ->where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($managerId) {
            $query->where('manager_id', $managerId);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($statusFilter) {
            if ($statusFilter === 'Absent') {
                $query->whereDoesntHave('todayAttendance', fn ($q) => $q->where('date', $formattedDate));
            } elseif ($statusFilter === 'On Shift') {
                $query->whereHas('todayAttendance', fn ($q) => $q->where('date', $formattedDate)->whereNotNull('check_in')->whereNull('check_out'));
            } else {
                $query->whereHas('todayAttendance', fn ($q) => $q->where('date', $formattedDate)->where('status', $statusFilter));
            }
        }

        $users = $query->paginate($perPage);

        $eligibleUserIds = User::excludeOwnerAndSelf()->where('status', 'active')->pluck('id');
        $allActiveCount = $eligibleUserIds->count();

        $todayAtts = Attendance::whereIn('user_id', $eligibleUserIds)
            ->where('date', $formattedDate)
            ->get();

        return [
            'date' => $formattedDate,
            'summary' => [
                'total_employees' => $allActiveCount,
                'present' => $todayAtts->where('status', 'Present')->count(),
                'late' => $todayAtts->where('status', 'Late')->count(),
                'absent' => max(0, $allActiveCount - $todayAtts->whereNotNull('check_in')->count()),
            ],
            'data' => $users,
        ];
    }

    public function getHrAttendanceExceptions(?string $date = null, ?int $departmentId = null, int $perPage = 15)
    {
        $query = Attendance::with(['user.department', 'companyLocation'])
            ->whereHas('user', function ($q) use ($departmentId) {
                $q->excludeOwnerAndSelf();
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
            })
            ->where('is_exception', true);

        if ($date) {
            $query->where('date', $date);
        }

        return $query->orderBy('date', 'desc')->paginate($perPage);
    }

    public function getHrMonthlySummary(int $month, int $year, ?int $departmentId = null, ?string $search = null, int $perPage = 15)
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $endDate->isFuture() ? now()->day : $startDate->daysInMonth;

        $query = User::query()
            ->excludeOwnerAndSelf()
            ->with(['department'])
            ->where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $users = $query->paginate($perPage);
        $shiftStart = Carbon::parse('09:00:00');

        $transformed = $users->getCollection()->map(function ($user) use ($startDate, $endDate, $daysInMonth, $shiftStart) {
            $attendances = Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $presentDays = $attendances->whereNotNull('check_in')->count();
            $lateDays = $attendances->where('status', 'Late')->count();
            $absentDays = max(0, $daysInMonth - $presentDays);
            $totalWorkedSeconds = $attendances->sum('worked_seconds');

            $totalLateMinutes = 0;
            foreach ($attendances->where('status', 'Late') as $att) {
                if ($att->check_in) {
                    $checkInTime = Carbon::parse($att->check_in->format('H:i:s'));
                    if ($checkInTime->gt($shiftStart)) {
                        $totalLateMinutes += (int) abs($shiftStart->diffInMinutes($checkInTime));
                    }
                }
            }

            return [
                'user' => $user,
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'late_minutes_total' => $totalLateMinutes,
                'absent_days' => $absentDays,
                'total_worked_seconds' => $totalWorkedSeconds,
            ];
        });

        $users->setCollection($transformed);

        return $users;
    }

    public function getHrMonthlySummaryAll(int $month, int $year, ?int $departmentId = null)
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $endDate->isFuture() ? now()->day : $startDate->daysInMonth;
        $shiftStart = Carbon::parse('09:00:00');

        $query = User::query()
            ->excludeOwnerAndSelf()
            ->with(['department'])
            ->where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $users = $query->get();

        return $users->map(function ($user) use ($startDate, $endDate, $daysInMonth, $shiftStart) {
            $attendances = Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $presentDays = $attendances->whereNotNull('check_in')->count();
            $lateDays = $attendances->where('status', 'Late')->count();
            $absentDays = max(0, $daysInMonth - $presentDays);
            $totalWorkedSeconds = $attendances->sum('worked_seconds');

            $totalLateMinutes = 0;
            foreach ($attendances->where('status', 'Late') as $att) {
                if ($att->check_in) {
                    $checkInTime = Carbon::parse($att->check_in->format('H:i:s'));
                    if ($checkInTime->gt($shiftStart)) {
                        $totalLateMinutes += (int) abs($shiftStart->diffInMinutes($checkInTime));
                    }
                }
            }

            return [
                'user' => $user,
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'late_minutes_total' => $totalLateMinutes,
                'absent_days' => $absentDays,
                'total_worked_seconds' => $totalWorkedSeconds,
            ];
        });
    }
}
