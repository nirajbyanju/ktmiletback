<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BlogService
{
    public function create(array $data, ?UploadedFile $thumbnail = null): BlogPost
    {
        try {
            $user = User::findOrFail($data['createdBy']);
            $userRoles = $user->roles()->pluck('name')->toArray();

            $data['isStatus'] = 0;
            if (in_array('Admin', $userRoles) || in_array('Super Admin', $userRoles)) {
                $data['isStatus'] = 1;
            }

            $title = $data['title'] ?? '';
            $baseSlug = Str::slug($title, '-');
            $slug = $baseSlug;

            $latestId = BlogPost::max('id');
            if (!is_null($latestId)) {
                $slug = $baseSlug . '-' . ($latestId + 1);
            }

            $filteredData = $this->filterData($data);
            $filteredData['slug'] = $slug;

            if ($thumbnail instanceof UploadedFile) {
                $filteredData['thumbnail'] = $this->handleFileUpload($thumbnail);
            }

            $blogPost = BlogPost::create($filteredData);

            $blogPost->slug = $slug . '-' . $blogPost->id;
            $blogPost->save();

            return $blogPost->load('category', 'user');
        } catch (\Exception $e) {
            Log::error('Error creating blog post', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw new \Exception("Error creating blog post: " . $e->getMessage());
        }
    }


    public function list(Request $request): LengthAwarePaginator
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
        $paginatedResults = $query->paginate($limit, ['*'], 'page', $page);

        return $paginatedResults;
    }

    public function show(BlogPost $blogPost): BlogPost
    {
        return $blogPost->load('category', 'user');
    }


    public function update(BlogPost $blogPost, array $data, ?UploadedFile $thumbnail = null): BlogPost
    {
        $filteredData = $this->filterData($data, true);
        if ($thumbnail instanceof UploadedFile && $thumbnail->isValid()) {
            $filteredData['thumbnail'] = $this->handleFileUpload($thumbnail);
        }

        $blogPost->update($filteredData);

        return $blogPost->fresh(['category', 'user']);
    }

    public function updateStatus(BlogPost $blogPost, int $status): BlogPost
    {
        $blogPost->is_status = $status;
        $blogPost->save();

        return $blogPost->fresh(['category', 'user']);
    }

    public function delete(BlogPost $blogPost): void
    {
        $blogPost->delete();
    }

    private function filterData(array $data, bool $partial = false): array
    {
        $fieldMap = [
            'title' => 'title',
            'entry' => 'entry',
            'author' => 'author',
            'categoryId' => 'category_id',
            'tags' => 'tags',
            'content' => 'content',
            'status' => 'status',
            'isStatus' => 'is_status',
            'scheduledPublishDate' => 'scheduled_publish_date',
        ];

        $filtered = [];

        foreach ($fieldMap as $requestKey => $column) {
            if (!$partial || array_key_exists($requestKey, $data)) {
                $filtered[$column] = $data[$requestKey] ?? null;
            }
        }

        if (!$partial) {
            $filtered['publish_date'] = now();
        }

        return $filtered;
    }

    private function handleFileUpload(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->move(public_path('opsh/blog/image'), $filename);
        $mappedData = asset('opsh/blog/image/' . $filename);
        return $mappedData;
    }
}
