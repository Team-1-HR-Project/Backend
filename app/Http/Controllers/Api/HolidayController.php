<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Holidays\StoreHolidayRequest;
use App\Http\Requests\Holidays\UpdateHolidayRequest;
use App\Models\Holiday;
use App\Services\Holidays\HolidayService;
use Illuminate\Http\JsonResponse;

class HolidayController extends Controller
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}

    /**
     * Display all holidays.
     */
    public function index(): JsonResponse
    {
        return ResponseHelper::success(
            data: $this->holidayService->getAll(),
            message: __('holidays.retrieved_successfully')
        );
    }

    /**
     * Create a new holiday.
     */
    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = $this->holidayService->create(
            $request->validated()
        );

        return ResponseHelper::success(
            data: $holiday,
            message: __('holidays.created_successfully'),
        );
    }

    /**
     * Update an existing holiday.
     */
    public function update(
        UpdateHolidayRequest $request,
        Holiday $holiday
    ): JsonResponse {
        $holiday = $this->holidayService->update(
            $holiday,
            $request->validated()
        );

        return ResponseHelper::success(
            data: $holiday,
            message: 'Holiday updated successfully.'
        );
    }

    /**
     * Delete an existing holiday.
     */
    public function destroy(Holiday $holiday): JsonResponse
    {
        $this->holidayService->delete($holiday);

        return ResponseHelper::success(
            data: null,
            message: 'Holiday deleted successfully.'
        );
    }
}
