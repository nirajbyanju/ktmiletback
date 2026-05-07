<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StatusUpdateRequest;
use App\Models\BlogPost;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BlogController extends BaseController
{
    protected BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }


    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['createdBy'] = Auth::id();
            $blogPost = $this->blogService->create($data, $request->file('thumbnail'));

            return response()->json([
                'success' => true,
                'data' => $blogPost,
                'message' => 'Blog post created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating blog post', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the blog post. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 500);
        }
    }


    public function index(Request $request): JsonResponse
    {
        $paginatedResults = $this->blogService->list($request);

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
            'success' => true,
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

    public function show(BlogPost $blogPost): JsonResponse
    {
        $data = $this->blogService->show($blogPost);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Blog post have been successfully listed',
        ], 200);
    }

    public function update(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $this->blogService->update($blogPost, $request->all(), $request->file('thumbnail'));

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Blog post have been successfully updated',
        ], 200);
    }

    public function updateStatus(StatusUpdateRequest $request, BlogPost $blogPost): JsonResponse
    {
        $data = $this->blogService->updateStatus(
            $blogPost,
            (int) $request->validated()['isStatus']
        );

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Blog post has been successfully updated',
        ], 200);
    }

    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $this->blogService->delete($blogPost);

        return response()->json([
            'success' => true,
            'message' => 'Blog post have been successfully delete',
        ], 200);
    }
}
