<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Http\Resources\PropertyFrontResources;
use App\Http\Resources\PropertyDetailResource;

class FrontController extends BaseController
{
    /**
     * Display a listing of properties with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function propertySummary(Request $request)
    {
        try {
            // Determine order
            $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC'])
                ? strtoupper($request->get('order_by'))
                : 'DESC';

            // Pagination parameters
            $limit = is_numeric($request->get('limit')) ? (int) $request->get('limit') : 10;
            $page = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

            // Fetch properties with relations and pagination
            $paginatedResults = Property::with([
                'propertyFace', 
                'listingType', 
                'propertyStatus', 
                'images',
                'address'
            ])
            // ->where('is_featured', 1) // Uncomment if needed
            ->orderBy('updated_at', $orderBy)
            ->paginate($limit, ['*'], 'page', $page);

            // Return JSON response with collection resource
            return response()->json([
                'status' => true,
                'message' => 'List of properties',
                'data' => PropertyFrontResources::collection($paginatedResults->getCollection()),
                'pagination' => [
                    'total' => $paginatedResults->total(),
                    'per_page' => $paginatedResults->perPage(),
                    'current_page' => $paginatedResults->currentPage(),
                    'last_page' => $paginatedResults->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

     public function propertyDetail($slug){
        try {
            $property = Property::with([
                'propertyFace', 
                'listingType', 
                'propertyStatus', 
                'images',
                'address'
            ])
                ->where('slug', $slug)
                ->first();

                if(!$property){
                    return response()->json([
                        'status' => false,
                        'message' => 'Property not found',
                        'error' => 'Property not found',
                    ], 404);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Property found',
                    'data' => new PropertyDetailResource($property),
                ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching property',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
        
}