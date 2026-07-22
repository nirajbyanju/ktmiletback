<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Admin CRUD for the public FAQ page (Website Content). */
class FaqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'success' => true,
            'message' => 'FAQs retrieved successfully.',
            'data' => Faq::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validated($request);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Faq::max('sort_order') + 1);

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully.',
            'data' => Faq::create($data),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $faq = Faq::findOrFail($id);
        $faq->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully.',
            'data' => $faq->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        Faq::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'FAQ deleted successfully.']);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'group_title' => [...$required, 'string', 'max:255'],
            'question' => [...$required, 'string', 'max:500'],
            'answer' => [...$required, 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['Super Admin', 'Admin']) && ! $user->can('manage_all'))) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can manage FAQs.');
        }
    }
}
