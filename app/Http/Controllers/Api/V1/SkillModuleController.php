<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SkillModule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SkillModuleController extends Controller
{
    public function index(Request $request)
    {
        $query = SkillModule::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('skill_name', 'LIKE', "%{$search}%")
                    ->orWhere('topics_covered', 'LIKE', "%{$search}%");
            });
        }

        $modules = $query->orderBy('id')->paginate($this->perPage($request));

        return $this->paginated($modules, 'Skill modules retrieved successfully.');
    }

    public function store(Request $request)
    {
        $module = SkillModule::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Skill module created successfully.',
            'data' => $module,
        ], Response::HTTP_CREATED);
    }

    public function show(int $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Skill module retrieved successfully.',
            'data' => SkillModule::findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $module = SkillModule::findOrFail($id);
        $module->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Skill module updated successfully.',
            'data' => $module->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        SkillModule::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Skill module deleted successfully.',
        ]);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return $request->validate([
            'skill_name' => [$required, 'string', 'max:50'],
            'topics_covered' => ['nullable', 'string'],
            'feedback_included' => ['sometimes', 'boolean'],
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
