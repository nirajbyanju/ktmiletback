<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactMessageController extends BaseController
{
    /**
     * Public — submit a contact message (no auth required)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'whatsapp_number' => 'required|string|max:20',
            'subject'         => 'required|string|max:255',
            'message'         => 'required|string|max:3000',
        ]);

        $message = ContactMessage::create([
            ...$validated,
            'status'     => 'new',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent. We will get back to you shortly.',
            'data'    => [
                'id'         => $message->id,
                'reference'  => 'KTM-MSG-' . str_pad($message->id, 5, '0', STR_PAD_LEFT),
                'created_at' => $message->created_at,
            ],
        ], 201);
    }

    /**
     * Admin — list all messages with search and filter
     */
    public function adminIndex(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = ContactMessage::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('subject', 'like', $search)
                  ->orWhere('whatsapp_number', 'like', $search);
            });
        }

        $messages = $query->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    /**
     * Admin — view a single message (auto-marks as read)
     */
    public function show(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($contactMessage->status === 'new') {
            $contactMessage->update([
                'status'  => 'read',
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $contactMessage->fresh(),
        ]);
    }

    /**
     * Admin — update status and notes
     */
    public function updateStatus(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status'      => ['required', Rule::in(ContactMessage::STATUSES)],
            'admin_notes' => 'nullable|string|max:3000',
        ]);

        $contactMessage->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data'    => $contactMessage->fresh(),
        ]);
    }

    /**
     * Admin — counts per status for dashboard summary
     */
    public function stats(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $counts = ContactMessage::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data'    => [
                'total'       => ContactMessage::count(),
                'new'         => $counts->get('new', 0),
                'read'        => $counts->get('read', 0),
                'in_progress' => $counts->get('in_progress', 0),
                'replied'     => $counts->get('replied', 0),
                'resolved'    => $counts->get('resolved', 0),
                'spam'        => $counts->get('spam', 0),
            ],
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();
        if (!$user) return false;
        return $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all');
    }
}
