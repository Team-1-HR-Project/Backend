<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFcmTokenRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = $request->user()
                ->notifications()
                ->paginate($request->get('per_page', 15));

            $paginatedData = NotificationResource::collection($notifications)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'notifications' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('notifications.retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('notifications.failed'),
                500
            );
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $count = $request->user()
                ->notifications()
                ->where('is_read', false)
                ->count();

            return ResponseHelper::success(
                ['unread_count' => $count],
                __('notifications.unread_count')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('notifications.failed'),
                500
            );
        }
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            $notification = Notification::find($id);

            if (! $notification) {
                return ResponseHelper::error(null, __('notifications.not_found'), 404);
            }

            if ($notification->user_id !== $request->user()->id) {
                return ResponseHelper::error(null, __('notifications.unauthorized'), 403);
            }

            $notification->update(['is_read' => true]);

            return ResponseHelper::success(
                new NotificationResource($notification),
                __('notifications.marked_as_read')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('notifications.failed'),
                500
            );
        }
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $request->user()
                ->notifications()
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return ResponseHelper::success(
                null,
                __('notifications.all_marked_as_read')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('notifications.failed'),
                500
            );
        }
    }

    public function clearAll(Request $request): JsonResponse
    {
        try {
            $request->user()
                ->notifications()
                ->delete();

            return ResponseHelper::success(
                null,
                __('notifications.all_cleared')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('notifications.failed'),
                500
            );
        }
    }

    public function updateFcmToken(UpdateFcmTokenRequest $request): JsonResponse
    {
        try {
            $request->user()->update([
                'fcm_token' => $request->fcm_token,
            ]);

            return ResponseHelper::success(
                null,
                __('notifications.fcm_token_updated')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('notifications.failed'),
                500
            );
        }
    }
}
