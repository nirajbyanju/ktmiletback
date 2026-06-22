<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    // ── Public ────────────────────────────────────────────────────────────────

    public function publicIndex(): JsonResponse
    {
        $testimonials = Testimonial::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Testimonials retrieved successfully.',
            'data'    => $testimonials,
        ]);
    }

    // ── Admin: full CRUD ──────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Testimonial::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('tag', 'LIKE', "%{$search}%")
                  ->orWhere('country', 'LIKE', "%{$search}%")
                  ->orWhere('meta', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $cat = $request->string('category');
            $query->whereJsonContains('cats', $cat->toString());
        }

        $perPage      = min(max((int) $request->query('per_page', 15), 1), 100);
        $testimonials = $query->orderBy('sort_order')->orderBy('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Testimonials retrieved successfully.',
            'data'    => [
                'data'         => $testimonials->items(),
                'current_page' => $testimonials->currentPage(),
                'last_page'    => $testimonials->lastPage(),
                'total'        => $testimonials->total(),
                'per_page'     => $testimonials->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['initials'] = $this->deriveInitials($data['name'] ?? '');
        $testimonial = Testimonial::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Testimonial created successfully.',
            'data'    => $testimonial,
        ], Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Testimonial retrieved successfully.',
            'data'    => $testimonial,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Testimonial updated successfully.',
            'data'    => $testimonial->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);
        $this->deletePhotoFile($testimonial);
        $testimonial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Testimonial deleted successfully.',
        ]);
    }

    // ── Photo management ──────────────────────────────────────────────────────

    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $testimonial = Testimonial::findOrFail($id);

        $this->deletePhotoFile($testimonial);

        $path = $request->file('photo')->store("testimonial_photos/{$id}", 'public');
        $testimonial->update(['photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully.',
            'data'    => [
                'photo'     => $path,
                'photo_url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    public function deletePhoto(int $id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);

        if (!$testimonial->photo) {
            return response()->json(['success' => false, 'message' => 'No photo to delete.'], 404);
        }

        $this->deletePhotoFile($testimonial);
        $testimonial->update(['photo' => null]);

        return response()->json(['success' => true, 'message' => 'Photo deleted successfully.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'name'        => [...$required, 'string', 'max:100'],
            'meta'        => [...$required, 'string', 'max:150'],
            'tag'         => [...$required, 'string', 'max:100'],
            'country'     => [...$required, 'string', 'max:100'],
            'quote'       => [...$required, 'string'],
            'cats'        => [...$required, 'array', 'min:1'],
            'cats.*'      => ['string', 'in:ielts,pte,abroad'],
            'is_featured' => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
        ]);
    }

    private function deriveInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        return $initials ?: '?';
    }

    private function deletePhotoFile(Testimonial $testimonial): void
    {
        if ($testimonial->photo && Storage::disk('public')->exists($testimonial->photo)) {
            Storage::disk('public')->delete($testimonial->photo);
        }
    }
}
