<?php

namespace App\Services;

use App\Models\User;

use App\Models\BlogPost;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Notifications\UserNotification;
use DateTime;

class BlogService
{

    public function createBlog(array $data): BlogPost
    {
        try {
            // Fetch the user by the provided 'createdBy' ID
            $user = User::findOrFail($data['createdBy']);
            $userRoles = $user->roles()->pluck('name')->toArray();

            // Set the default status
            $data['isStatus'] = 0;
            if (in_array('Admin', $userRoles) || in_array('Super Admin', $userRoles)) {
                $data['isStatus'] = 1;
            }

            $title = $data['title'] ?? ''; // Get the title
            $baseSlug = Str::slug($title, '-'); // Generate the base slug
            $slug = $baseSlug; // Initialize the slug with the base slug

            // Find the latest ID and append it to the slug
            $latestId = BlogPost::max('id'); // Get the latest ID from the BlogPost table
            if (!is_null($latestId)) {
                $slug = $baseSlug . '-' . ($latestId + 1); // Append latest ID + 1 to the slug
            }
            $filteredData = $this->filterData($data);
            $filteredData['slug'] = $slug;

            if (request()->hasFile('thumbnail')) {
                $filteredData['thumbnail'] = $this->handleFileUpload(request()->file('thumbnail'));
            }

            $blogPost = BlogPost::create($filteredData);
            $filteredData['content'] = \addHeadingIds($data['content']);

            // Update the slug with the blog post ID appended at the end
            $blogPost->slug = $slug . '-' . $blogPost->id;
            $blogPost->save();
            // $user->notify(new UserNotification("New Pop messgae added by {$user->first_name} {$user->last_name}"));

            // Return the created blog post with its category
            return BlogPost::with('category')->find($blogPost->id);
        } catch (\Exception $e) {
            // Log the full error message and stack trace
            Log::error("Error creating blog post: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            // Throw a custom exception or return a response with the error message
            throw new \Exception("Error creating blog post: " . $e->getMessage());
        }
    }


    public function listActiveBlogPost($request)
    {
        $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC']) ? strtoupper($request->get('order_by')) : 'DESC';
        $limit = is_numeric($request->get('limit')) ? $request->get('limit') : 10;
        $page = is_numeric($request->get('page')) ? $request->get('page') : 1;

        $filters = [
            'category_id' => $request->get('categoryId'),
            'is_status' => $request->get('isStatus'),
            'title' => $request->get('searchTerm'),
            'created_at' => $request->get('createdAt'),
        ];

        $query = BlogPost::with('category', 'user');

        foreach ($filters as $column => $value) {
            if (!empty($value)) {
                if ($column === 'title') {
                    $query->where($column, 'like', '%' . $value . '%');
                } elseif ($column === 'created_at') {
                    // Use whereDate for proper date comparison
                    $query->whereDate('created_at', '=', $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }

        $query->orderBy('id', $orderBy);
        // Paginate the results
        $paginatedResults = $query->paginate($limit, ['*'], 'page', $page);
        // Return the paginated response
        return $paginatedResults;
    }

    public function getBlogPostById($id)
    {
        return BlogPost::findorFail($id);
    }


    public function getUpdateById(int $id, array $data): array
    {
        $blogPost = BlogPost::find($id);

        if (!$blogPost) {
            return [
                'message' => 'Blog post not found',
                'status' => 404
            ];
        }

        $filteredData = $this->filterData($data);
        if (request()->hasFile('thumbnail') && request()->file('thumbnail')->isValid()) {
            $filteredData['thumbnail'] = $this->handleFileUpload(request()->file('thumbnail'));
        }

        $blogPost->update($filteredData);

        return [
            'message' => 'Blog post updated successfully'
        ];
    }

    public function getUpdateStatusById($id, $data)
    {
        $blogPost = BlogPost::findOrFail($id);
        $blogPost->isStatus = $data['isStatus'];
        $blogPost->save();
        return $blogPost;
    }

    public function getDeleteById($id)
    {
        $data = BlogPost::find($id);

        if (!$data) {
            return response()->json(['message' => 'data not found.'], 404);
        }

        $data->delete();

        return response()->json(['message' => 'Blog post deleted successfully.'], 200);
    }

    private function filterData(array $data): array
    {
        return [
            'title' => $data['title'],
            'entry' => $data['entry'] ?? null,
            'author' => $data['author'] ?? null,
            'category_id' => $data['categoryId'],
            'tags' => $data['tags'],
            'content' => $data['content'],
            'status' => $data['status'],
            'is_status' => $data['isStatus'],
            'publish_date' => now(),
            'scheduled_publish_date' => $data['scheduledPublishDate'] ?? null,
            // 'view_count' => $data['viewCount'],
            // 'like_count' => $data['likeCount'] ,
            // 'bookmark_count' => $data['bookmarkCount'],
        ];
    }

    private function handleFileUpload($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->move(public_path('opsh/blog/image'), $filename);
        $mappedData = asset('opsh/blog/image/' . $filename);
        return $mappedData;
    }
}
