<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyEvents\StoreCompanyEventRequest;
use App\Http\Requests\CompanyEvents\UpdateCompanyEventRequest;
use App\Models\CompanyEvent;
use App\Services\CompanyEvents\CompanyEventService;
use Illuminate\Http\JsonResponse;

class CompanyEventController extends Controller
{
    public function __construct(
        protected CompanyEventService $companyEventService
    ) {}

    public function index(): JsonResponse
    {
        $events = $this->companyEventService->getAll();

        return ResponseHelper::success(
            data: $events,
            message: __('company_events.retrieved_successfully')
        );
    }

    public function store(StoreCompanyEventRequest $request): JsonResponse
    {
        $event = $this->companyEventService->create(
            user: $request->user(),
            data: $request->validated()
        );

        return ResponseHelper::success(
            data: $event,
            message: __('company_events.created_successfully')
        );
    }

    public function update(UpdateCompanyEventRequest $request, CompanyEvent $companyEvent): JsonResponse
    {
        $event = $this->companyEventService->update(
            companyEvent: $companyEvent,
            data: $request->validated()
        );

        return ResponseHelper::success(
            data: $event,
            message: __('company_events.updated_successfully')
        );
    }

    public function destroy(CompanyEvent $companyEvent): JsonResponse
    {
        $this->companyEventService->delete($companyEvent);

        return ResponseHelper::success(
            message: __('company_events.deleted_successfully')
        );
    }
}
