<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Property;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
        return [
            // new Middleware('permission:edit articles', only: ['edit', 'update']),
            // new Middleware('permission:create articles', only: ['create', 'store']),
            // new Middleware('permission:delete articles', only: ['delete', 'destroy']),
            // new Middleware('permission:view articles', only: ['index', 'show']),
        ];
    }
    public function index(Request $request)
    {
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
    }

    public function store(Request $request)
    {
        try {

            $vacancy = $this->propertyStoreService->store($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Vacancy created successfully.',
                'data' => $vacancy,
            ], Response::HTTP_CREATED); // Using constant for better readability

        } catch (\Illuminate\Validation\ValidationException $e) {
            // This will be caught automatically by Laravel if using FormRequest
            // But we'll handle it explicitly for clarity
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
    public function show($id)
    {
        return Property::findOrFail($id);
    }
    public function update(Request $request, $id)
    {
        $property = Property::findOrFail($id);
        $property->update($request->all());
        return $property;
    }
    public function destroy($id)
    {
        Property::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    public function updateStatus($id, $request)
    {
        $property = Property::findOrFail($id);
        $property->status = $request->get('status');
        $property->save();
        return $property;
    }
}
