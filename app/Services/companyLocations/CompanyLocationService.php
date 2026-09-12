<?php

namespace App\Services\CompanyLocations;

use App\Models\CompanyLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CompanyLocationService
{
    /**
     * Create the company's single location.
     */
    public function create(array $data): CompanyLocation
    {
        // Prevent creating more than one company location.
        if (CompanyLocation::exists()) {
            throw ValidationException::withMessages([
                'location' => [
                    'The company already has a location.',
                ],
            ]);
        }

        $this->validateCoordinates(
            (float) $data['latitude'],
            (float) $data['longitude']
        );

        $this->validateRadius(
            (float) $data['radius']
        );

        return CompanyLocation::create([
            'name' => $data['name'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius' => $data['radius'],
            'created_by' => Auth::id(),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Get company location by ID.
     */
    public function getLocation(int $id): CompanyLocation
    {
        return CompanyLocation::findOrFail($id);
    }

    /**
     * Update company location.
     */
    public function update(int $id, array $data): CompanyLocation
    {
        // Get the existing location from database.
        $location = $this->getLocation($id);

        // Validate coordinates if latitude or longitude is updated.
        if (
            isset($data['latitude']) ||
            isset($data['longitude'])
        ) {
            $this->validateCoordinates(
                (float) ($data['latitude'] ?? $location->latitude),
                (float) ($data['longitude'] ?? $location->longitude)
            );
        }

        // Validate radius if it is updated.
        if (isset($data['radius'])) {
            $this->validateRadius(
                (float) $data['radius']
            );
        }

        // Update only the values sent in the request.
        $location->update($data);

        // Return the updated record.
        return $location->fresh();
    }

    /**
     * Deactivate company location.
     */
    public function deactivate(int $id): CompanyLocation
    {
        $location = $this->getLocation($id);

        $location->update([
            'is_active' => false,
        ]);

        return $location->fresh();
    }

    /**
     * Activate company location.
     */
    public function activate(int $id): CompanyLocation
    {
        $location = $this->getLocation($id);

        $location->update([
            'is_active' => true,
        ]);

        return $location->fresh();
    }

    /**
     * Get active company location.
     */
    public function getActiveLocation(): CompanyLocation
    {
        return CompanyLocation::where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Validate latitude and longitude.
     */
    private function validateCoordinates(
        float $latitude,
        float $longitude
    ): void {
        if ($latitude < -90 || $latitude > 90) {
            throw ValidationException::withMessages([
                'latitude' => [
                    'Latitude must be between -90 and 90.',
                ],
            ]);
        }

        if ($longitude < -180 || $longitude > 180) {
            throw ValidationException::withMessages([
                'longitude' => [
                    'Longitude must be between -180 and 180.',
                ],
            ]);
        }
    }

    /**
     * Validate attendance radius.
     */
    private function validateRadius(float $radius): void
    {
        if ($radius <= 0) {
            throw ValidationException::withMessages([
                'radius' => [
                    'Radius must be greater than zero.',
                ],
            ]);
        }
    }
}
