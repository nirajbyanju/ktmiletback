<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\MessageTemplate;
use App\Services\TemplateMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class MessageTemplateController extends Controller
{
    public function __construct(private readonly TemplateMailer $mailer) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'success' => true,
            'data' => MessageTemplate::orderBy('category')->orderBy('group_label')->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $template = MessageTemplate::findOrFail($id);

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string', 'max:10000'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template saved. The next email will use this wording.',
            'data' => $template->fresh(),
        ]);
    }

    /** Restore the original wording shipped with the system. */
    public function reset(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $template = MessageTemplate::findOrFail($id);
        $template->update([
            'subject' => $template->default_subject,
            'body' => $template->default_body,
            'cta_text' => $template->default_cta_text,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template restored to its original wording.',
            'data' => $template->fresh(),
        ]);
    }

    /** Send this template (with sample data) to the logged-in admin's inbox. */
    public function sendTest(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $template = MessageTemplate::findOrFail($id);

        if ($template->category === 'whatsapp') {
            return response()->json(['success' => false, 'message' => 'WhatsApp templates cannot be emailed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rendered = $this->mailer->render($template, $this->sampleData());

        try {
            Mail::send('emails.branded', $rendered['view'], function ($message) use ($request, $rendered) {
                $message->to($request->user()->email)
                    ->subject('[TEST] '.$rendered['subject'])
                    ->replyTo(config('mail.reply_to.address', 'ktmtestpreparation@ktmeducational.edu.np'));
            });
        } catch (\Throwable $e) {
            EmailLog::create([
                'user_id' => $request->user()->id,
                'recipient' => $request->user()->email,
                'template_key' => $template->key,
                'subject' => '[TEST] '.$rendered['subject'],
                'related_type' => 'test_send',
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test send failed: '.$e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Test sends appear in Email History too
        EmailLog::create([
            'user_id' => $request->user()->id,
            'recipient' => $request->user()->email,
            'template_key' => $template->key,
            'subject' => '[TEST] '.$rendered['subject'],
            'body_html' => view('emails.branded', $rendered['view'])->render(),
            'related_type' => 'test_send',
            'status' => 'sent',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test email sent to '.$request->user()->email.' (with sample data).',
        ]);
    }

    /** Render preview HTML with sample data (shown inside the admin page). */
    public function preview(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $template = MessageTemplate::findOrFail($id);
        // Preview unsaved edits if provided
        if ($request->filled('body')) {
            $template->body = $request->string('body');
        }
        if ($request->filled('subject')) {
            $template->subject = $request->string('subject');
        }
        if ($request->has('cta_text')) {
            $template->cta_text = $request->input('cta_text');
        }

        $rendered = $this->mailer->render($template, $this->sampleData());

        return response()->json([
            'success' => true,
            'data' => [
                'subject' => $rendered['subject'],
                'html' => view('emails.branded', $rendered['view'])->render(),
            ],
        ]);
    }

    /** Email history log. */
    public function logs(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = EmailLog::with('user:id,first_name,last_name,email')
            ->select(['id', 'user_id', 'recipient', 'template_key', 'subject', 'related_type', 'related_id', 'status', 'error', 'created_at'])
            ->latest('id');

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(fn ($q) => $q->where('recipient', 'like', $s)->orWhere('template_key', 'like', $s));
        }
        if ($request->filled('template_key')) {
            $query->where('template_key', $request->template_key);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(min(max((int) $request->query('limit', 25), 1), 100)),
        ]);
    }

    /** Full snapshot of one sent email (for the history viewer). */
    public function showLog(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $log = EmailLog::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'subject' => $log->subject,
                'html' => $log->body_html,
            ],
        ]);
    }

    private function sampleData(): array
    {
        return [
            'StudentName' => 'Sita Sharma', 'FirstName' => 'Sita',
            'CourseName' => 'IELTS Academic', 'PlanName' => 'Value Batch', 'ItemName' => 'IELTS Academic — Value Batch',
            'InvoiceNumber' => 'INV-20260712-SAMPLE', 'PaymentAmount' => 'NPR 4,999',
            'StartDate' => '15 Jul 2026', 'EndDate' => '26 Aug 2026',
            'ClassDays' => 'Mon–Fri', 'ClassTime' => '8:00 – 9:00 AM', 'TeacherName' => 'Priya Shrestha',
            'ZoomMeetingID' => '123 456 7890', 'AttendancePercent' => '72%',
            'TestName' => 'IELTS', 'ExamFormat' => 'IELTS Academic', 'ExamDate' => '2026-08-10', 'ExamCentre' => 'Kathmandu',
            'DemoDate' => '18 Jul 2026', 'DemoTime' => '6:00 PM', 'AdminNotes' => '',
            'SubscriptionStart' => '12 Jul 2026', 'SubscriptionEnd' => '11 Aug 2026',
            'TicketID' => 'KTM-MSG-00042', 'TicketSubject' => 'Class time question', 'ReplyPreview' => 'We have updated your class time as requested.',
            'RefundAmount' => 'NPR 2,199', 'RefundReason' => 'Batch changed before start',
            'ResetLink' => 'https://www.ktmtestpreparation.com/reset-password?token=sample',
            'PasswordChangeDate' => '12 Jul 2026', 'OfferExpiry' => '31 Jul 2026',
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['Super Admin', 'Admin']) && ! $user->can('manage_all'))) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can manage message templates.');
        }
    }
}
