<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlogPost;

class BlogsController extends Controller
{
    public function view(Request $request)
    {
        try {
            $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC'])
                ? strtoupper($request->get('order_by'))
                : 'DESC';

            $limit = is_numeric($request->get('limit')) ? (int) $request->get('limit') : 10;
            $page = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

            $filters = [
                'category_id' => $request->get('category_id'),
            ];

            $query = BlogPost::with('user','category');

            foreach ($filters as $column => $value) {
                if (!is_null($value)) {
                    if ($column === 'status') {
                        $query->where($column, 'like', '%' . $value . '%');
                    } else {
                        $query->where($column, $value);
                    }
                }
            }

            if ($request->filled('category_slug')) {
                $query->whereHas('category', function ($categoryQuery) use ($request) {
                    $categoryQuery->where('slug', $request->get('category_slug'));
                });
            }
            // $query->where('status', '3');
            // $query->where('is_status', '1');

            $query->orderBy('id', $orderBy);

            $paginatedResults = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $paginatedResults,
                'message' => 'Data fetched successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function details($slug)
    {
        try {
            $blogs = BlogPost::with('comments', 'user')
                ->where('slug', $slug)
                ->first();
            return response()->json([
                'success' => true,
                'data' => $blogs,
                'message' => 'Data fetched successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewing($category)
    {
        try {
            if ($category) {
                $posting = BlogPost::with('user.userdetail')->where('category_id', $category)->get();
            } else {
                $posting = BlogPost::with('user.userdetail')->get();
            }
            return response()->json([
                'success' => true,
                'data' => $posting,
                'message' => 'Data fetched successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
