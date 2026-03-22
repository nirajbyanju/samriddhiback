<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BlogService;
use App\Http\Requests\StatusUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BlogController extends BaseController
{
    protected $BlogService;

    public function __construct(BlogService $categoryService)
    {
        $this->BlogService = $categoryService;
    }


    public function create(Request $request)
    {
        try {
            $data = $request->all();
            $data['createdBy'] = Auth::user()->id;
            $createBlog = $this->BlogService->createBlog($data);

            return response()->json([
                'success' => true,
                'data' => $createBlog,
                'message' => 'Blog post created successfully',
            ], 201);
        } catch (\Exception $e) {
            // Log the full error message and stack trace
            Log::error("Error in create blog post: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            // Return a response with the error message
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the blog post. Please try again later.',
                'error' => $e->getMessage(),  // This will still send the exception message back
            ], 500);  // HTTP 500 Internal Server Error
        }
    }


    public function list(Request $request)
    {
        $paginatedResults = $this->BlogService->listActiveBlogPost($request);

        if ($paginatedResults->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No posts available',
                'data' => [],
                'pagination' => [
                    'total' => $paginatedResults->total(), // Total records
                    'per_page' => $paginatedResults->perPage(), // Items per page
                    'current_page' => $paginatedResults->currentPage(), // Current page
                    'last_page' => $paginatedResults->lastPage(), // Last page number
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

    public function listing($id): JsonResponse
    {
        $data = $this->BlogService->getBlogPostById($id);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Blog post have been successfully listed',
        ], 200);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $data = $this->BlogService->getUpdateById($id, $request->all());
        return response()->json([
            'success' => true,
            'data' =>  $data,
            'message' => 'Blog post have been successfully updated',
        ], 200);
    }

    public function updateStatus($id, StatusUpdateRequest $request)
    {
        // Fetch the data using the service
        $data = $this->BlogService->getUpdateStatusById($id, $request);

        // Return a JSON response with success status and updated data
        return response()->json([
            'success' => true,
            'data' =>  $data,  // Assuming 'category' is correct
            'message' => 'Blog post has been successfully updated',
        ], 200);
    }

    public function delete($id): JsonResponse
    {
        $data = $this->BlogService->getDeleteById($id);
        return response()->json([
            'success' => true,
            'data' => [
                'blogPost' => $data,
            ],
            'message' => 'Blog post have been successfully delete',
        ], 200);
    }
}
