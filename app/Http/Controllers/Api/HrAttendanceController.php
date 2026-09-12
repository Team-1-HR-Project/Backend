<?php

namespace App\Http\Controllers\Api;

use App\Exports\HrMonthlySummaryExport;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetHrDailyAttendanceRequest;
use App\Http\Requests\GetHrExceptionsAttendanceRequest;
use App\Http\Requests\GetHrMonthlySummaryRequest;
use App\Http\Resources\HrAttendanceExceptionResource;
use App\Http\Resources\HrDailyAttendanceResource;
use App\Http\Resources\HrMonthlySummaryResource;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HrAttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function daily(GetHrDailyAttendanceRequest $request): JsonResponse
    {
        $result = $this->attendanceService->getHrDailyAttendance(
            $request->date,
            $request->department_id ? (int) $request->department_id : null,
            $request->manager_id ? (int) $request->manager_id : null,
            $request->status,
            $request->search,
            $request->filled('per_page') ? (int) $request->per_page : 15
        );

        return ResponseHelper::success(
            data: [
                'date' => $result['date'],
                'summary' => $result['summary'],
                'employees' => HrDailyAttendanceResource::collection($result['data'])->response()->getData(true),
            ],
            message: 'Company daily attendance retrieved successfully.'
        );
    }

    public function exceptions(GetHrExceptionsAttendanceRequest $request): JsonResponse
    {
        $exceptions = $this->attendanceService->getHrAttendanceExceptions(
            $request->date,
            $request->department_id ? (int) $request->department_id : null,
            $request->filled('per_page') ? (int) $request->per_page : 15
        );

        return ResponseHelper::success(
            data: HrAttendanceExceptionResource::collection($exceptions)->response()->getData(true),
            message: 'Attendance exceptions retrieved successfully.'
        );
    }

    public function monthlySummary(GetHrMonthlySummaryRequest $request): JsonResponse
    {
        $summary = $this->attendanceService->getHrMonthlySummary(
            (int) $request->month,
            (int) $request->year,
            $request->department_id ? (int) $request->department_id : null,
            $request->search,
            $request->filled('per_page') ? (int) $request->per_page : 15
        );

        return ResponseHelper::success(
            data: HrMonthlySummaryResource::collection($summary)->response()->getData(true),
            message: 'Monthly attendance summary retrieved successfully.'
        );
    }

    public function export(GetHrMonthlySummaryRequest $request): BinaryFileResponse
    {
        $fileName = sprintf('attendance_summary_%02d_%d.xlsx', $request->month, $request->year);

        return Excel::download(
            new HrMonthlySummaryExport(
                $this->attendanceService,
                (int) $request->month,
                (int) $request->year,
                $request->department_id ? (int) $request->department_id : null
            ),
            $fileName
        );
    }
}
