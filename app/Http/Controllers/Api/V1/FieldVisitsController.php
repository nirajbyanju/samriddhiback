<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FieldVisits;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\FieldVisitsService;
use Illuminate\Http\Response;

class FieldVisitsController extends BaseController
{
    protected $fieldVisitsService;

    public function __construct(FieldVisitsService $fieldVisitsService)
    {
        $this->fieldVisitsService = $fieldVisitsService;
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
        $paginatedResults = $this->fieldVisitsService->listActiveFieldVisits($request);

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

            $fieldVisit = $this->fieldVisitsService->store($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Vacancy created successfully.',
                'data' => $fieldVisit,
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
        return FieldVisits::findOrFail($id);
    }
    public function update(Request $request, $id)
    {
        $fieldVisit = FieldVisits::findOrFail($id);
        $fieldVisit->update($request->all());
        return $fieldVisit;
    }
    public function destroy($id)
    {
        FieldVisits::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    public function updateStatus($id)
    {
        $fieldVisit = FieldVisits::findOrFail($id);
        $fieldVisit->is_status = request()->get('isStatus');
        $fieldVisit->save();
        return $fieldVisit;
    }



    public function frontTour(Request $request)
    {
        try {

            $request->validate([
                'property_id' => 'required|integer',
                'date' => 'required|date',
                'time' => 'required',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email',
                'message' => 'nullable|string'
            ]);

            $tour = FieldVisits::create([
                'property_id' => $request->property_id,
                'date' => $request->date,
                'time' => $request->time,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'message' => $request->message,
                'accept_term' => $request->accept_term
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Tour booked successfully',
                'data' => $tour
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Error booking tour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
