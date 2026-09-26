<?php

namespace App\Http\Controllers\Api;

use App\Enums\EvaluationPeriodStatus;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationCategoryRequest;
use App\Http\Requests\StoreEvaluationPeriodRequest;
use App\Http\Resources\EvaluationCategoryResource;
use App\Http\Resources\EvaluationPeriodResource;
use App\Http\Resources\EvaluationResource;
use App\Models\EvaluationCategory;
use App\Models\EvaluationPeriod;
use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HrEvaluationSetupController extends Controller
{
    public function __construct(private EvaluationService $evaluationService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $periodId = $request->query('period_id') ? (int) $request->query('period_id') : null;
            $departmentId = $request->query('department_id') ? (int) $request->query('department_id') : null;
            $userId = $request->query('user_id') ?? $request->query('employee_id');
            $evaluatorId = $request->query('evaluator_id') ? (int) $request->query('evaluator_id') : null;

            $evaluations = $this->evaluationService->getHrEvaluationsOverview(
                $status,
                $periodId,
                $departmentId,
                $userId ? (int) $userId : null,
                $evaluatorId,
                10
            );

            $paginatedData = EvaluationResource::collection($evaluations)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'evaluations' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('evaluation.overview_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('evaluation.failed_overview'),
                500
            );
        }
    }

    public function listPeriods(): JsonResponse
    {
        try {
            $periods = EvaluationPeriod::latest()->paginate(10);

            $paginatedData = EvaluationPeriodResource::collection($periods)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'periods' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('evaluation.periods_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(null, __('evaluation.failed_periods'), 500);
        }
    }

    public function storePeriod(StoreEvaluationPeriodRequest $request): JsonResponse
    {
        try {
            $period = EvaluationPeriod::create($request->validated());

            return ResponseHelper::success(
                new EvaluationPeriodResource($period),
                __('evaluation.period_created'),
                201
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(null, __('evaluation.failed_create_period'), 500);
        }
    }

    public function togglePeriodStatus(int $id): JsonResponse
    {
        try {
            $period = EvaluationPeriod::find($id);
            if (! $period) {
                return ResponseHelper::error(null, __('evaluation.period_not_found'), 404);
            }

            $newStatus = $period->status === EvaluationPeriodStatus::ACTIVE
                ? EvaluationPeriodStatus::CLOSED
                : EvaluationPeriodStatus::ACTIVE;

            $period->update(['status' => $newStatus]);

            $statusText = __('evaluation.statuses.'.$newStatus->value);

            return ResponseHelper::success(
                new EvaluationPeriodResource($period),
                __('evaluation.period_status_changed', ['status' => $statusText])
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(null, __('evaluation.failed_update_status'), 500);
        }
    }

    public function listCategories(): JsonResponse
    {
        try {
            $categories = EvaluationCategory::latest()->paginate(10);

            $paginatedData = EvaluationCategoryResource::collection($categories)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'categories' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('evaluation.categories_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(null, __('evaluation.failed_categories'), 500);
        }
    }

    public function storeCategory(StoreEvaluationCategoryRequest $request): JsonResponse
    {
        try {
            $category = EvaluationCategory::create($request->validated());

            return ResponseHelper::success(
                new EvaluationCategoryResource($category),
                __('evaluation.category_created'),
                201
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(null, __('evaluation.failed_create_category'), 500);
        }
    }
}
