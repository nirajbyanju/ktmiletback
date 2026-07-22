<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\DemoRequest;
use App\Models\Enrollment;
use App\Models\Teacher;
use App\Services\TemplateMailer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Classes the current user may mark attendance for.
     * Admins see every active class; a teacher sees only their own.
     */
    public function classes(Request $request)
    {
        $query = Batch::query()
            ->with(['course:id,course_name', 'teacher:id,name'])
            ->where('is_active', true);

        if (! $this->isManageAll($request)) {
            $teacher = $this->teacherRecord($request);
            if (! $teacher) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $query->where('teacher_id', $teacher->id);
        }

        $classes = $query->orderBy('id')->get()->map(fn (Batch $b) => [
            'id' => $b->id,
            'label' => trim(($b->course?->course_name ?? 'Other').' — '.($b->batch_type ?? '')),
            'course_name' => $b->course?->course_name,
            'batch_type' => $b->batch_type,
            'class_time' => $b->class_time,
            'teacher_name' => $b->teacher?->name,
        ]);

        return response()->json(['success' => true, 'data' => $classes]);
    }

    /**
     * Roster for a class on a given day, with each student's mark for that day.
     */
    public function roster(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'date' => ['required', 'date'],
        ]);

        $batch = Batch::with(['course:id,course_name', 'teacher:id,name'])->findOrFail($validated['batch_id']);
        $this->authorizeBatch($request, $batch);

        $date = Carbon::parse($validated['date'])->toDateString();

        // Enrolled (genuinely active) students of this batch.
        $enrollments = Enrollment::with('user:id,first_name,last_name,email,phone')
            ->where('batch_id', $batch->id)
            ->whereNull('archived_at')
            ->whereIn('payment_status', ['confirmed', 'fee_waived'])
            ->orderBy('id')
            ->get();

        // Approved demo (trial) students booked into THIS specific class on this day.
        // A demo is tied to one class via batch_id (set when the admin approves it),
        // so it no longer appears across every class its teacher runs.
        $demos = DemoRequest::whereNull('archived_at')
            ->where('status', 'approved')
            ->where('batch_id', $batch->id)
            ->whereDate('scheduled_at', $date)
            ->orderBy('id')
            ->get();

        $enrollmentMarks = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->where('attended_on', $date)->get()->keyBy('enrollment_id');
        $demoMarks = Attendance::whereIn('demo_request_id', $demos->pluck('id'))
            ->where('attended_on', $date)->get()->keyBy('demo_request_id');

        $students = [];

        foreach ($enrollments as $e) {
            $name = $e->user
                ? trim("{$e->user->first_name} {$e->user->last_name}")
                : ($e->student_name ?? '—');
            $students[] = [
                'type' => 'enrolled',
                'enrollment_id' => $e->id,
                'demo_request_id' => null,
                'name' => $name ?: ($e->student_name ?? '—'),
                'email' => $e->email ?? $e->user?->email,
                'phone' => $e->phone ?? $e->user?->phone,
                'attendance_percentage' => $e->attendance_percentage,
                'status' => $enrollmentMarks->get($e->id)?->status,
            ];
        }

        foreach ($demos as $d) {
            $students[] = [
                'type' => 'demo',
                'enrollment_id' => null,
                'demo_request_id' => $d->id,
                'name' => $d->name ?? '—',
                'email' => $d->email,
                'phone' => $d->phone,
                'attendance_percentage' => null,
                'status' => $demoMarks->get($d->id)?->status,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'batch' => [
                    'id' => $batch->id,
                    'label' => trim(($batch->course?->course_name ?? 'Other').' — '.($batch->batch_type ?? '')),
                    'course_name' => $batch->course?->course_name,
                    'class_time' => $batch->class_time,
                    'teacher_name' => $batch->teacher?->name,
                ],
                'date' => $date,
                'can_edit' => $this->canEditOn($request, $date),
                'students' => $students,
            ],
        ]);
    }

    /**
     * One student's attendance record (dated list + summary) for the class they
     * belong to. Authorized like marking: teachers only their own classes.
     */
    public function history(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => ['nullable', 'integer', 'exists:enrollments,id'],
            'demo_request_id' => ['nullable', 'integer', 'exists:demo_requests,id'],
        ]);

        if (empty($validated['enrollment_id']) && empty($validated['demo_request_id'])) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Provide enrollment_id or demo_request_id.');
        }

        if (! empty($validated['enrollment_id'])) {
            $enrollment = Enrollment::with('user:id,first_name,last_name')->findOrFail($validated['enrollment_id']);
            $this->authorizeBatch($request, Batch::findOrFail($enrollment->batch_id));
            $name = $enrollment->user
                ? trim("{$enrollment->user->first_name} {$enrollment->user->last_name}")
                : $enrollment->student_name;
            $type = 'enrolled';
            $records = Attendance::with('markedBy:id,first_name,last_name')
                ->where('enrollment_id', $enrollment->id)->orderByDesc('attended_on')->get();
        } else {
            $demo = DemoRequest::findOrFail($validated['demo_request_id']);
            $this->authorizeBatch($request, Batch::findOrFail($demo->batch_id));
            $name = $demo->name;
            $type = 'demo';
            $records = Attendance::with('markedBy:id,first_name,last_name')
                ->where('demo_request_id', $demo->id)->orderByDesc('attended_on')->get();
        }

        $present = $records->where('status', Attendance::STATUS_PRESENT)->count();
        $total = $records->count();

        return response()->json([
            'success' => true,
            'data' => [
                'student_name' => $name ?: '—',
                'type' => $type,
                'summary' => [
                    'present' => $present,
                    'total' => $total,
                    'percentage' => $total > 0 ? (int) round($present / $total * 100) : null,
                ],
                'records' => $records->map(fn (Attendance $r) => [
                    'attended_on' => $r->attended_on->toDateString(),
                    'status' => $r->status,
                    'marked_by_name' => $r->markedBy
                        ? trim("{$r->markedBy->first_name} {$r->markedBy->last_name}")
                        : null,
                ])->values(),
            ],
        ]);
    }

    /**
     * Save (create/update) marks for a class on a day.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'date' => ['required', 'date'],
            'marks' => ['required', 'array', 'min:1'],
            'marks.*.enrollment_id' => ['nullable', 'integer', 'exists:enrollments,id'],
            'marks.*.demo_request_id' => ['nullable', 'integer', 'exists:demo_requests,id'],
            'marks.*.status' => ['required', 'in:present,absent'],
        ]);

        $batch = Batch::with('teacher:id,name')->findOrFail($validated['batch_id']);
        $this->authorizeBatch($request, $batch);

        $date = Carbon::parse($validated['date'])->toDateString();

        // Teachers may only mark the current day; admins may mark any day.
        if (! $this->canEditOn($request, $date)) {
            return response()->json([
                'success' => false,
                'message' => 'Teachers can only mark attendance for today. Please ask an admin to edit past days.',
            ], Response::HTTP_FORBIDDEN);
        }

        $userId = $request->user()->id;
        $touchedEnrollments = [];

        DB::transaction(function () use ($validated, $batch, $date, $userId, &$touchedEnrollments) {
            foreach ($validated['marks'] as $mark) {
                if (! empty($mark['enrollment_id'])) {
                    // The student must actually belong to this class.
                    $belongs = Enrollment::where('id', $mark['enrollment_id'])
                        ->where('batch_id', $batch->id)->exists();
                    if (! $belongs) {
                        continue;
                    }
                    Attendance::updateOrCreate(
                        ['enrollment_id' => $mark['enrollment_id'], 'attended_on' => $date],
                        ['demo_request_id' => null, 'batch_id' => $batch->id, 'status' => $mark['status'], 'marked_by' => $userId]
                    );
                    $touchedEnrollments[$mark['enrollment_id']] = true;
                } elseif (! empty($mark['demo_request_id'])) {
                    $belongs = DemoRequest::where('id', $mark['demo_request_id'])
                        ->where('batch_id', $batch->id)->exists();
                    if (! $belongs) {
                        continue;
                    }
                    Attendance::updateOrCreate(
                        ['demo_request_id' => $mark['demo_request_id'], 'attended_on' => $date],
                        ['enrollment_id' => null, 'batch_id' => $batch->id, 'status' => $mark['status'], 'marked_by' => $userId]
                    );
                }
            }
        });

        // Keep each enrolled student's overall attendance % in sync.
        foreach (array_keys($touchedEnrollments) as $enrollmentId) {
            $this->recomputePercentage((int) $enrollmentId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance saved.',
        ]);
    }

    /** Recalculate an enrollment's attendance_percentage from its marks. */
    private function recomputePercentage(int $enrollmentId): void
    {
        $enrollment = Enrollment::with(['user', 'batch.course'])->find($enrollmentId);
        if (! $enrollment) {
            return;
        }

        $oldPct = $enrollment->attendance_percentage;

        $records = Attendance::where('enrollment_id', $enrollmentId)->get();
        $total = $records->count();
        $present = $records->where('status', Attendance::STATUS_PRESENT)->count();
        $newPct = $total > 0 ? round($present / $total * 100, 2) : null;

        $enrollment->update(['attendance_percentage' => $newPct]);

        // Warn the student the first time their attendance drops below 80% (the
        // certificate threshold). Once per enrollment — the mailer de-dupes on
        // the [attendance_warning, id] key so a later dip won't re-send.
        $wasOk = $oldPct === null || (float) $oldPct >= 80;
        if ($total > 0 && $newPct !== null && (float) $newPct < 80 && $wasOk && $enrollment->user) {
            app(TemplateMailer::class)->sendToUser('attendance_warning', $enrollment->user, [
                'CourseName' => $enrollment->batch?->course?->course_name ?? 'your course',
                'AttendancePercent' => round((float) $newPct).'%',
            ], ['related' => ['attendance_warning', $enrollment->id]]);
        }
    }

    private function isManageAll(Request $request): bool
    {
        return $request->user()->hasAnyRole(['Super Admin', 'Admin']) || $request->user()->can('manage_all');
    }

    private function teacherRecord(Request $request): ?Teacher
    {
        return Teacher::where('user_id', $request->user()->id)->first();
    }

    /** Admins mark any day; teachers only today. */
    private function canEditOn(Request $request, string $date): bool
    {
        if ($this->isManageAll($request)) {
            return true;
        }

        return $date === now()->toDateString();
    }

    private function authorizeBatch(Request $request, Batch $batch): void
    {
        if ($this->isManageAll($request)) {
            return;
        }

        $teacher = $this->teacherRecord($request);
        if (! $teacher || (int) $batch->teacher_id !== (int) $teacher->id) {
            abort(Response::HTTP_FORBIDDEN, 'You can only mark attendance for your own classes.');
        }
    }
}
