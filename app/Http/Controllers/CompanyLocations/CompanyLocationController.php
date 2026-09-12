<?php

namespace App\Http\Controllers\CompanyLocations;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyLocationRequest;
use App\Http\Requests\UpdateCompanyLocationRequest;
use App\Services\CompanyLocations\CompanyLocationService;

class CompanyLocationController extends Controller
{
    public function __construct(
        private CompanyLocationService $companyLocationService
    ) {}

    /**
     * Create company location.
     */
    public function store(StoreCompanyLocationRequest $request)
    {
        $location = $this->companyLocationService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Company location created successfully.',
            'data' => $location,
        ], 201);
    }

    /**
     * Update company location.
     */
    public function update(
        UpdateCompanyLocationRequest $request,
        int $id
    ) {
        $location = $this->companyLocationService->update(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Company location updated successfully.',
            'data' => $location,
        ]);
    }

    /**
     * Deactivate company location.
     */
    public function deactivate(int $id)
    {
        $location = $this->companyLocationService->deactivate($id);

        return response()->json([
            'success' => true,
            'message' => 'Company location deactivated successfully.',
            'data' => $location,
        ]);
    }

    /**
     * Activate company location.
     */
    public function activate(int $id)
    {
        $location = $this->companyLocationService->activate($id);

        return response()->json([
            'success' => true,
            'message' => 'Company location activated successfully.',
            'data' => $location,
        ]);
    }

    /**
     * Get active company location.
     */
    public function activeLocation()
    {
        $location = $this->companyLocationService->getActiveLocation();

        return response()->json([
            'success' => true,
            'message' => 'Active company location retrieved successfully.',
            'data' => $location,
        ]);
    }
}
