<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeHrFieldsRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Jobs\SendNotificationJob;
use App\Services\EmployeeService;
use App\Services\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $user = $this->employeeService->createEmployee($request->validated());

            return ResponseHelper::success(
                data: new UserResource($user),
                message: __('employees.created_successfully'),
                statusCode: Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('employees.failed_to_create'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->employeeService->getEmployeeById($id);

            Gate::authorize('view', $user);

            return ResponseHelper::success(
                data: new UserResource($user),
                message: __('employees.retrieved_details_successfully')
            );
        } catch (AuthorizationException $e) {
            return ResponseHelper::error(
                message: __('employees.unauthorized_view_profile'),
                statusCode: Response::HTTP_FORBIDDEN
            );
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::error(
                message: __('employees.not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('employees.failed_to_retrieve_details'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function updateHrFields(UpdateEmployeeHrFieldsRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->employeeService->getEmployeeById($id);

            Gate::authorize('updateHrFields', $user);

            $updatedUser = $this->employeeService->updateHrFields($user, $request->validated());

            return ResponseHelper::success(
                data: new UserResource($updatedUser),
                message: __('employees.hr_fields_updated')
            );
        } catch (AuthorizationException $e) {
            return ResponseHelper::error(
                message: __('employees.unauthorized_update_hr_fields'),
                statusCode: Response::HTTP_FORBIDDEN
            );
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::error(
                message: __('employees.not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('employees.failed_to_update_hr_fields'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->employeeService->updateProfile(
                auth('api')->user(),
                $request->validated()
            );

            return ResponseHelper::success(
                data: new UserResource($user),
                message: __('employees.profile_updated_successfully')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('employees.failed_to_update_profile'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function changeAccountStatus(int $id, NotificationService $notificationService): JsonResponse
    {
        try {
            $user = $this->employeeService->changeAccountStatus($id);

            $isActive = $user->status === 'active';

            $message = $isActive
                ? __('employees.account_activated')
                : __('employees.account_deactivated');

            SendNotificationJob::dispatch(
                user: $user,
                type: $isActive ? 'account_activated' : 'account_deactivated',
                titleKey: $isActive ? 'notifications.account_activated_title' : 'notifications.account_deactivated_title',
                bodyKey: $isActive ? 'notifications.account_activated_body' : 'notifications.account_deactivated_body',
                parameters: [],
                metadata: [
                    'screen' => 'profile_overview',
                    'status' => $user->status,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );

            return ResponseHelper::success(
                data: new UserResource($user),
                message: $message
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::error(
                message: __('employees.not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('employees.failed_to_change_status'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'status',
                'department_id',
                'manager_id',
                'employment_type',
                'role',
            ]);

            $perPage = (int) $request->get('per_page', 15);
            $employees = $this->employeeService->getAllEmployees($filters, $perPage);

            $paginatedData = UserResource::collection($employees)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'employees' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('employees.retrieved_successfully')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('employees.failed_to_retrieve'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
