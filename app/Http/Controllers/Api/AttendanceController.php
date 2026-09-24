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
use App\Jobs\SendNotificationJob;
use App\Services\AttendanceService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function today(GetTodayAttendanceRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return ResponseHelper::error(
                message: __('attendance.user_not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        $todayData = $this->attendanceService->getTodayData(
            $user,
            $request->filled('latitude') ? (float) $request->latitude : null,
            $request->filled('longitude') ? (float) $request->longitude : null
        );

        return ResponseHelper::success(
            data: new TodayAttendanceResource($todayData),
            message: __('attendance.today_retrieved')
        );
    }

    public function checkIn(CheckInRequest $request, NotificationService $notificationService): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return ResponseHelper::error(
                message: __('attendance.user_not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        try {
            $attendance = $this->attendanceService->checkIn(
                $user,
                (float) $request->latitude,
                (float) $request->longitude
            );

            $checkInTime = Carbon::parse($attendance->check_in)->format('g:i A');

            SendNotificationJob::dispatch(
                user: $user,
                type: 'checkin_success',
                titleKey: 'notifications.checkin_success_title',
                bodyKey: 'notifications.checkin_success_body',
                parameters: [
                    'time' => $checkInTime,
                ],
                metadata: [
                    'screen' => 'attendance_history',
                    'attendance_id' => $attendance->id,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );

            return ResponseHelper::success(
                data: new AttendanceResource($attendance),
                message: __('attendance.checkin_success'),
                statusCode: Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $errorResponses = [
                'DUPLICATE_CHECKIN' => [Response::HTTP_UNPROCESSABLE_ENTITY, __('attendance.errors.duplicate_checkin')],
                'OUTSIDE_RADIUS' => [Response::HTTP_UNPROCESSABLE_ENTITY, __('attendance.errors.outside_radius')],
                'LOCATION_NOT_CONFIGURED' => [Response::HTTP_UNPROCESSABLE_ENTITY, __('attendance.errors.location_not_configured')],
            ];

            [$statusCode, $message] = $errorResponses[$e->getMessage()] ?? [Response::HTTP_BAD_REQUEST, $e->getMessage()];

            return ResponseHelper::error(
                message: $message,
                statusCode: $statusCode
            );
        }
    }

    public function checkOut(NotificationService $notificationService): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return ResponseHelper::error(
                message: __('attendance.user_not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        try {
            $attendance = $this->attendanceService->checkOut($user);

            $checkOutTime = Carbon::parse($attendance->check_out)->format('g:i A');

            SendNotificationJob::dispatch(
                user: $user,
                type: 'checkout_success',
                titleKey: 'notifications.checkout_success_title',
                bodyKey: 'notifications.checkout_success_body',
                parameters: [
                    'time' => $checkOutTime,
                ],
                metadata: [
                    'screen' => 'attendance_history',
                    'attendance_id' => $attendance->id,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );

            return ResponseHelper::success(
                data: new AttendanceResource($attendance),
                message: __('attendance.checkout_success')
            );
        } catch (\Exception $e) {
            $errorResponses = [
                'NO_OPEN_CHECKIN' => [Response::HTTP_UNPROCESSABLE_ENTITY, __('attendance.errors.no_open_checkin')],
                'ALREADY_CHECKED_OUT' => [Response::HTTP_UNPROCESSABLE_ENTITY, __('attendance.errors.already_checked_out')],
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
        $user = auth('api')->user();

        if (! $user) {
            return ResponseHelper::error(
                message: __('attendance.user_not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        $paginatedHistory = $this->attendanceService->getHistory(
            $user,
            $request->filled('month') ? (int) $request->month : null,
            $request->filled('year') ? (int) $request->year : null,
            $request->filled('per_page') ? (int) $request->per_page : 15
        );

        $responseData = AttendanceHistoryResource::collection($paginatedHistory)->response()->getData(true);

        $formattedData = [
            'history' => $responseData['data'],
            'links'   => $responseData['links'],
            'meta'    => $responseData['meta'],
        ];

        return ResponseHelper::success(
            data: $formattedData,
            message: __('attendance.history_retrieved')
        );
    }
}
