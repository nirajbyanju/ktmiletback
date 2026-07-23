<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ContactMessage;
use App\Services\TemplateMailer;
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
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'whatsapp_number' => 'required|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:3000',
        ]);

        $message = ContactMessage::create([
            ...$validated,
            // Public route, but if the visitor is logged in (token sent), link
            // the message to their account so their dashboard can list it.
            'user_id' => auth('sanctum')->id(),
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        app(TemplateMailer::class)->send('support_received', $message->email, [
            'StudentName' => $message->full_name,
            'FirstName' => explode(' ', trim($message->full_name))[0] ?? $message->full_name,
            'TicketID' => 'KTM-MSG-'.str_pad($message->id, 5, '0', STR_PAD_LEFT),
            'TicketSubject' => $message->subject,
        ], ['user_id' => $message->user_id, 'related' => ['support_received', $message->id]]);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent. We will get back to you shortly.',
            'data' => [
                'id' => $message->id,
                'reference' => 'KTM-MSG-'.str_pad($message->id, 5, '0', STR_PAD_LEFT),
                'created_at' => $message->created_at,
            ],
        ], 201);
    }

    /**
     * Student — list their own support requests (matched by account or email)
     */
    public function myIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        $messages = ContactMessage::where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if ($user->email) {
                $q->orWhere('email', $user->email);
            }
        })
            ->whereNull('archived_at')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['id', 'subject', 'message', 'status', 'admin_notes', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Admin — list all messages with search and filter
     */
    public function adminIndex(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = ContactMessage::orderBy('created_at', 'desc');

        // Archived records are hidden by default; ?archived=1 shows only them
        $request->boolean('archived')
            ? $query->whereNotNull('archived_at')
            : $query->whereNull('archived_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
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
            'data' => $messages,
        ]);
    }

    /**
     * Admin — view a single message (auto-marks as read)
     */
    public function show(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($contactMessage->status === 'new') {
            $contactMessage->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $contactMessage->fresh(),
        ]);
    }

    /**
     * Admin — update status and notes
     */
    public function updateStatus(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(ContactMessage::STATUSES)],
            'admin_notes' => 'nullable|string|max:3000',
        ]);

        $previousNotes = $contactMessage->admin_notes;
        $previousStatus = $contactMessage->status;

        $contactMessage->update($validated);

        $mailer = app(TemplateMailer::class);
        $ticketId = 'KTM-MSG-'.str_pad($contactMessage->id, 5, '0', STR_PAD_LEFT);
        $base = [
            'StudentName' => $contactMessage->full_name,
            'FirstName' => explode(' ', trim($contactMessage->full_name))[0] ?? $contactMessage->full_name,
            'TicketID' => $ticketId,
            'TicketSubject' => $contactMessage->subject,
        ];

        if ($validated['status'] === 'resolved' && $previousStatus !== 'resolved') {
            $mailer->send('support_resolved', $contactMessage->email, $base,
                ['user_id' => $contactMessage->user_id, 'related' => ['support_resolved', $contactMessage->id]]);
        } elseif (! empty($validated['admin_notes']) && $validated['admin_notes'] !== $previousNotes) {
            $mailer->send('support_reply', $contactMessage->email, [
                ...$base,
                'ReplyPreview' => mb_substr($validated['admin_notes'], 0, 200),
            ], ['user_id' => $contactMessage->user_id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => $contactMessage->fresh(),
        ]);
    }

    /**
     * Admin — counts per status for dashboard summary
     */
    public function stats(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $counts = ContactMessage::whereNull('archived_at')
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => ContactMessage::whereNull('archived_at')->count(),
                'new' => $counts->get('new', 0),
                'read' => $counts->get('read', 0),
                'in_progress' => $counts->get('in_progress', 0),
                'replied' => $counts->get('replied', 0),
                'resolved' => $counts->get('resolved', 0),
                'spam' => $counts->get('spam', 0),
            ],
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all');
    }
}
