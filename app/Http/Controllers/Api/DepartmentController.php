<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Services\DepartmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DepartmentController extends Controller
{
    public function __construct(private DepartmentService $departmentService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'status']);
            $perPage = (int) $request->get('per_page', 15);
            $departments = $this->departmentService->getAllDepartments($filters, $perPage);
            $paginatedData = DepartmentResource::collection($departments)->response()->getData(true);

            return ResponseHelper::success(
                data: $paginatedData,
                message: __('departments.retrieved_successfully')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('departments.failed_to_process'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        try {
            $department = $this->departmentService->createDepartment($request->validated());

            return ResponseHelper::success(
                data: new DepartmentResource($department),
                message: __('departments.created_successfully'),
                statusCode: Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('departments.failed_to_process'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        try {
            $department = $this->departmentService->updateDepartment($id, $request->validated());

            return ResponseHelper::success(
                data: new DepartmentResource($department),
                message: __('departments.updated_successfully')
            );
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::error(
                message: __('departments.not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('departments.failed_to_process'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function changeStatus(int $id): JsonResponse
    {
        try {
            $department = $this->departmentService->changeDepartmentStatus($id);

            $message = $department->status === 'active'
                ? __('departments.activated_successfully')
                : __('departments.deactivated_successfully');

            return ResponseHelper::success(
                data: new DepartmentResource($department),
                message: $message
            );
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::error(
                message: __('departments.not_found'),
                statusCode: Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('departments.failed_to_process'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function GetManagersDropdown(): JsonResponse
    {
        $managers = $this->departmentService->getManagersForDropdown();

        return ResponseHelper::success(
            data: $managers,
            message: __('departments.managers_retrieved_successfully'),
            statusCode: Response::HTTP_OK
        );
    }
}
