<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Website Content — admin-editable texts (announcement bar, contact details,
 * homepage hero, demo video links) stored in the settings table, plus the FAQ.
 * Public payload feeds the live site; values are seeded to match what was
 * previously hardcoded, so nothing changes until the admin edits it.
 */
class SiteContentController extends Controller
{
    /** Editable keys and their validation rules. */
    private const KEYS = [
        'announcement_enabled' => ['nullable', 'boolean'],
        'announcement_text' => ['nullable', 'string', 'max:300'],
        'announcement_expires_at' => ['nullable', 'date'],
        'contact_phone' => ['nullable', 'string', 'max:50'],
        'contact_mobile' => ['nullable', 'string', 'max:50'],
        'contact_email' => ['nullable', 'email', 'max:255'],
        'contact_address' => ['nullable', 'string', 'max:255'],
        'contact_hours' => ['nullable', 'string', 'max:255'],
        'hero_badge' => ['nullable', 'string', 'max:200'],
        'hero_title' => ['nullable', 'string', 'max:300'],
        'hero_subtitle' => ['nullable', 'string', 'max:1000'],
        'demo_video_ielts' => ['nullable', 'string', 'max:255'],
        'demo_video_pte' => ['nullable', 'string', 'max:255'],
    ];

    /** Public: content payload for the live site. */
    public function publicShow(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Site content retrieved successfully.',
            'data' => $this->payload(),
        ]);
    }

    /** Public: active FAQs grouped for the /faq page. */
    public function publicFaqs(): JsonResponse
    {
        $faqs = Faq::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'group_title', 'question', 'answer']);

        // Preserve group order by first appearance
        $groups = [];
        foreach ($faqs as $faq) {
            $groups[$faq->group_title][] = ['q' => $faq->question, 'a' => $faq->answer];
        }

        return response()->json([
            'success' => true,
            'message' => 'FAQs retrieved successfully.',
            'data' => collect($groups)
                ->map(fn ($items, $title) => ['title' => $title, 'items' => $items])
                ->values(),
        ]);
    }

    /** Admin: current values for the Website Content page. */
    public function show(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'success' => true,
            'message' => 'Site content retrieved successfully.',
            'data' => $this->payload(),
        ]);
    }

    /** Admin: save edited values. */
    public function update(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate(
            collect(self::KEYS)->mapWithKeys(fn ($rules, $key) => [$key => ['sometimes', ...$rules]])->all()
        );

        foreach ($data as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''));
        }

        return response()->json([
            'success' => true,
            'message' => 'Website content saved successfully.',
            'data' => $this->payload(),
        ]);
    }

    private function payload(): array
    {
        $values = [];
        foreach (array_keys(self::KEYS) as $key) {
            $values[$key] = Setting::get($key, '');
        }
        $values['announcement_enabled'] = Setting::getBool('announcement_enabled', false);

        return $values;
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['Super Admin', 'Admin']) && ! $user->can('manage_all'))) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can manage website content.');
        }
    }
}
