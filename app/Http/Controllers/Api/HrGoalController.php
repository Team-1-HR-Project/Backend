<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoalResource;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HrGoalController extends Controller
{
    public function __construct(private GoalService $goalService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $departmentId = $request->query('department_id') ? (int) $request->query('department_id') : null;
            $userId = $request->query('user_id') ?? $request->query('employee_id');

            $goals = $this->goalService->getHrGoalsOverview(
                $status,
                $departmentId,
                $userId ? (int) $userId : null
            );

            $paginatedData = GoalResource::collection($goals)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'goals' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('goal.company_overview')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('goal.failed_company_overview'),
                500
            );
        }
    }
}
