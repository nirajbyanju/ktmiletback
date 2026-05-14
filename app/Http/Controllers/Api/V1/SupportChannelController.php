<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SupportChannelController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportChannel::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('channel_type', 'LIKE', "%{$search}%")
                    ->orWhere('contact_value', 'LIKE', "%{$search}%");
            });
        }

        $channels = $query->orderBy('id')->paginate($this->perPage($request));

        return $this->paginated($channels, 'Support channels retrieved successfully.');
    }

    public function store(Request $request)
    {
        $channel = SupportChannel::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Support channel created successfully.',
            'data' => $channel,
        ], Response::HTTP_CREATED);
    }

    public function show(int $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Support channel retrieved successfully.',
            'data' => SupportChannel::findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $channel = SupportChannel::findOrFail($id);
        $channel->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Support channel updated successfully.',
            'data' => $channel->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        SupportChannel::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Support channel deleted successfully.',
        ]);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return $request->validate([
            'channel_type' => [$required, 'string', 'max:50'],
            'contact_value' => [$required, 'string', 'max:255'],
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('limit', 10), 1), 100);
    }

    private function paginated($paginator, string $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
