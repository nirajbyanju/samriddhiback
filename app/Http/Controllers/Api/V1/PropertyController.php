<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Property;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Services\PropertyStoreService;
use Illuminate\Http\Response;

class PropertyController extends BaseController  implements HasMiddleware
{
    protected PropertyStoreService $propertyStoreService;

    public function __construct(PropertyStoreService $propertyStoreService)
    {
        $this->propertyStoreService = $propertyStoreService;
    }
    public static function middleware(): array
    {
        return [];
    }

    public function index(Request $request)
    {
        $paginatedResults = $this->propertyStoreService->listActiveProperty($request);

        if ($paginatedResults->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No posts available',
                'data' => [],
                'pagination' => [
                    'total' => $paginatedResults->total(),
                    'per_page' => $paginatedResults->perPage(),
                    'current_page' => $paginatedResults->currentPage(),
                    'last_page' => $paginatedResults->lastPage(),
                ],
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'List of posts',
            'data' => $paginatedResults->items(),
            'pagination' => [
                'total' => $paginatedResults->total(), // Total records
                'per_page' => $paginatedResults->perPage(), // Items per page
                'current_page' => $paginatedResults->currentPage(), // Current page 
                'last_page' => $paginatedResults->lastPage(), // Last page number
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $property = $this->propertyStoreService->store($this->preparePayload($request));

            return response()->json([
                'success' => true,
                'message' => 'Property created successfully.',
                'data' => $property,
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating vacancy.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function show(Property $property)
    {
        $property->load([
            'address',
            'images',
            'nearbyPlaces',
            'houseDetails',
            'houseDetails.furnishing',
            'houseDetails.houseType',
            'houseDetails.constructionStatus',
            'houseDetails.roofType',
            'houseDetails.buildingFace',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Property details fetched successfully',
            'data' => $property,
        ], 200);
    }

    public function update(Request $request, Property $property)
    {
        $property = $this->propertyStoreService->update($property, $this->preparePayload($request));

        return response()->json([
            'success' => true,
            'message' => 'Property updated successfully.',
            'data' => $property,
        ]);
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return response()->json([
            'success' => true,
            'message' => 'Property deleted successfully.',
        ]);
    }

    public function updateStatus(Request $request, Property $property)
    {
        $validated = $request->validate([
            'isStatus' => 'nullable|boolean',
            'is_status' => 'nullable|boolean',
        ]);

        $property->is_status = $validated['is_status'] ?? $validated['isStatus'] ?? $property->is_status;
        $property->save();

        return response()->json([
            'success' => true,
            'message' => 'Property status updated successfully.',
            'data' => $property,
        ]);
    }

    private function preparePayload(Request $request): array
    {
        $payload = $this->normalizePayload($request->all());

        foreach ([
            'base_price',
            'advertise_price',
            'land_area',
            'length',
            'height',
            'road_width',
            'built_area',
            'parking_area',
        ] as $field) {
            if ($request->has($field)) {
                $payload[$field] = $request->input($field);
            }
        }

        if ($request->has('construction_status_id')) {
            $payload['construction_status'] = $request->input('construction_status_id');
        }

        return $payload;
    }

    private function normalizePayload(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizePayload($item);
            }

            return $normalized;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($trimmed === 'null') {
            return null;
        }

        if ($trimmed === '') {
            return $value;
        }

        $decoded = json_decode($trimmed, true);

        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded) || $decoded === null)) {
            return $this->normalizePayload($decoded);
        }

        return $value;
    }
}
