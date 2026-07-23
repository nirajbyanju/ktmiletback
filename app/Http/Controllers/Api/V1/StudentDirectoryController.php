<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\Enrollment;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\MockTestEnrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Admin — central student directory: list all students and show a single
 * student's complete profile (classes, demos, mock tests, exam bookings,
 * payments, support requests) in one payload.
 */
class StudentDirectoryController extends Controller
{
    private const STAFF_ROLES = ['Super Admin', 'Admin', 'Employee', 'Teacher'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $search = trim((string) $request->get('search'));

        $students = User::query()
            ->select('id', 'userCode', 'first_name', 'middle_name', 'last_name', 'username', 'email', 'phone', 'status', 'created_at')
            // Students = everyone who is not staff
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::STAFF_ROLES))
            ->withCount(['enrollments'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('userCode', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(min(max((int) $request->query('limit', 20), 1), 100));

        return response()->json(['success' => true, 'data' => $students]);
    }

    public function show(Request $request, int $userId): JsonResponse
    {
        $this->authorizeAdmin($request);

        $student = User::with('roles:id,name')->findOrFail($userId);

        $enrollments = Enrollment::with([
            'batch:id,course_id,batch_type,class_time,start_date,end_date,schedule_notes,teacher_id',
            'batch.course:id,course_name',
            'batch.teacher:id,name',
            'invoice:id,invoice_number,status,crm_payment_status,total_npr',
        ])->where('user_id', $student->id)->whereNull('archived_at')->latest('id')->get();

        $demoRequests = DemoRequest::where('user_id', $student->id)->whereNull('archived_at')->latest('id')->get();

        $mockTests = MockTestEnrollment::with(['subscription', 'invoice:id,invoice_number,status,crm_payment_status,total_npr'])
            ->where('user_id', $student->id)->whereNull('archived_at')->latest('id')->get();

        $examBookings = ExamBookingEnrollment::with([
            'examBooking:id,exam_name,exam_type,price,discount',
            'invoice:id,invoice_number,status,crm_payment_status,total_npr',
        ])->where('user_id', $student->id)->whereNull('archived_at')->latest('id')->get();

        $invoices = Invoice::with([
            'batch:id,course_id,batch_type', 'batch.course:id,course_name',
            'mockTestSubscription:id,subscriptions_name',
            'examBookingEnrollment:id,exam_booking_id',
            'examBookingEnrollment.examBooking:id,exam_name,exam_type',
        ])->where('user_id', $student->id)->latest('id')->get()
            ->map(function ($inv) {
                $inv->setAttribute('invoice_type', $inv->type);

                return $inv;
            });

        $supportRequests = ContactMessage::where(function ($q) use ($student) {
            $q->where('user_id', $student->id);
            if ($student->email) {
                $q->orWhere('email', $student->email);
            }
        })
            ->whereNull('archived_at')
            ->latest('id')
            ->get(['id', 'subject', 'message', 'status', 'admin_notes', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $student,
                'enrollments' => $enrollments,
                'demo_requests' => $demoRequests,
                'mock_tests' => $mockTests,
                'exam_bookings' => $examBookings,
                'invoices' => $invoices,
                'support_requests' => $supportRequests,
            ],
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['Super Admin', 'Admin']) && ! $user->can('manage_all'))) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can access the student directory.');
        }
    }
}
