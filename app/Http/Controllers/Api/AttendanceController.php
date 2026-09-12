<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;
use App\Http\Requests\GetAttendanceHistoryRequest;
use App\Http\Requests\GetTodayAttendanceRequest;
use App\Http\Resources\AttendanceHistoryResource;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\TodayAttendanceResource;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function today(GetTodayAttendanceRequest $request): JsonResponse
    {
        $employee = auth('api')->user()?->employee;

        if (! $employee) {
            return ResponseHelper::error(
                message: 'Employee profile not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        $todayData = $this->attendanceService->getTodayData(
            $employee,
            $request->filled('latitude') ? (float) $request->latitude : null,
            $request->filled('longitude') ? (float) $request->longitude : null
        );

        return ResponseHelper::success(
            data: new TodayAttendanceResource($todayData),
            message: 'Today attendance retrieved successfully.'
        );
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $employee = auth('api')->user()?->employee;

        if (! $employee) {
            return ResponseHelper::error(
                message: 'Employee profile not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        try {
            $attendance = $this->attendanceService->checkIn(
                $employee,
                (float) $request->latitude,
                (float) $request->longitude
            );

            return ResponseHelper::success(
                data: new AttendanceResource($attendance),
                message: 'Checked in successfully.',
                statusCode: Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $errorResponses = [
                'DUPLICATE_CHECKIN' => [Response::HTTP_UNPROCESSABLE_ENTITY, 'You have already checked in today.'],
                'OUTSIDE_RADIUS' => [Response::HTTP_UNPROCESSABLE_ENTITY, 'You are outside the allowed company location radius.'],
                'LOCATION_NOT_CONFIGURED' => [Response::HTTP_UNPROCESSABLE_ENTITY, 'Company location is not assigned or active for your profile.'],
            ];

            [$statusCode, $message] = $errorResponses[$e->getMessage()] ?? [Response::HTTP_BAD_REQUEST, $e->getMessage()];

            return ResponseHelper::error(
                message: $message,
                statusCode: $statusCode
            );
        }
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $employee = auth('api')->user()?->employee;

        if (! $employee) {
            return ResponseHelper::error(
                message: 'Employee profile not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        try {
            $attendance = $this->attendanceService->checkOut(
                $employee,
                (float) $request->latitude,
                (float) $request->longitude
            );

            return ResponseHelper::success(
                data: new AttendanceResource($attendance),
                message: 'Checked out successfully.'
            );
        } catch (\Exception $e) {
            $errorResponses = [
                'NO_OPEN_CHECKIN' => [Response::HTTP_UNPROCESSABLE_ENTITY, 'No active check-in record found for today.'],
                'ALREADY_CHECKED_OUT' => [Response::HTTP_UNPROCESSABLE_ENTITY, 'You have already checked out today.'],
                'OUTSIDE_RADIUS' => [Response::HTTP_UNPROCESSABLE_ENTITY, 'You are outside the allowed company location radius.'],
            ];

            [$statusCode, $message] = $errorResponses[$e->getMessage()] ?? [Response::HTTP_BAD_REQUEST, $e->getMessage()];

            return ResponseHelper::error(
                message: $message,
                statusCode: $statusCode
            );
        }
    }

    public function history(GetAttendanceHistoryRequest $request): JsonResponse
    {
        $employee = auth('api')->user()?->employee;

        if (! $employee) {
            return ResponseHelper::error(
                message: 'Employee profile not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        $paginatedHistory = $this->attendanceService->getHistory(
            $employee,
            $request->filled('month') ? (int) $request->month : null,
            $request->filled('year') ? (int) $request->year : null,
            $request->filled('per_page') ? (int) $request->per_page : 15
        );

        return ResponseHelper::success(
            data: AttendanceHistoryResource::collection($paginatedHistory)->response()->getData(true),
            message: 'Attendance history retrieved successfully.'
        );
    }
}
