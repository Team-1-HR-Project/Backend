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
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HrAttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function daily(GetHrDailyAttendanceRequest $request): JsonResponse
    {
        try {
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
                message: __('hr.daily_attendance_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('hr.failed_to_retrieve'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function exceptions(GetHrExceptionsAttendanceRequest $request): JsonResponse
    {
        try {
            $exceptions = $this->attendanceService->getHrAttendanceExceptions(
                $request->date,
                $request->department_id ? (int) $request->department_id : null,
                $request->filled('per_page') ? (int) $request->per_page : 15
            );
            $paginatedExceptions = HrAttendanceExceptionResource::collection($exceptions)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'exceptions' => $paginatedExceptions['data'],
                    'links' => $paginatedExceptions['links'],
                    'meta' => $paginatedExceptions['meta'],
                ],
                message: __('hr.exceptions_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('hr.failed_to_retrieve'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function monthlySummary(GetHrMonthlySummaryRequest $request): JsonResponse
    {
        try {
            $summary = $this->attendanceService->getHrMonthlySummary(
                (int) $request->month,
                (int) $request->year,
                $request->department_id ? (int) $request->department_id : null,
                $request->search,
                $request->filled('per_page') ? (int) $request->per_page : 15
            );
            $paginatedSummary = HrMonthlySummaryResource::collection($summary)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'summary' => $paginatedSummary['data'],
                    'links' => $paginatedSummary['links'],
                    'meta' => $paginatedSummary['meta'],
                ],
                message: __('hr.monthly_summary_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('hr.failed_to_retrieve'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
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
