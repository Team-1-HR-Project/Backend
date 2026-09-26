<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoalResource;
use App\Http\Resources\UserResource;
use App\Services\EmployeeService;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ManagerController extends Controller
{
    public function __construct(private EmployeeService $employeeService, private GoalService $goalService) {}

    public function employees(Request $request): JsonResponse
    {
        try {
            $manager = auth('api')->user();
            if (! $manager) {
                return ResponseHelper::error(
                    message: __('auth.unauthenticated'),
                    statusCode: Response::HTTP_UNAUTHORIZED
                );
            }
            $filters = $request->only(['search', 'status', 'department_id', 'employment_type', 'role']);
            $filters['manager_id'] = $manager->id;
            $perPage = (int) $request->get('per_page', 15);
            $employees = $this->employeeService->getAllEmployees($filters, $perPage);
            $paginatedData = UserResource::collection($employees)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'employees' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('manager.employees_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('manager.failed_to_retrieve'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function teamGoals(Request $request): JsonResponse
    {
        try {
            $manager = $request->user();

            if (! $manager) {
                return ResponseHelper::error(null, __('goal.manager_not_found'), 404);
            }

            $status = $request->query('status');
            $userId = $request->query('user_id') ?? $request->query('employee_id');

            $goals = $this->goalService->getManagerTeamGoals(
                $manager,
                $status,
                $userId ? (int) $userId : null
            );

            $paginatedData = GoalResource::collection($goals)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'goals' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('goal.team_goals_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('goal.failed_team_goals'),
                500
            );
        }
    }
}
