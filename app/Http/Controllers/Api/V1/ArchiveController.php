<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\Enrollment;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\MockTestEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Admin — archive / restore records. "Delete" in the admin panel never
 * destroys data: it stamps archived_at, hiding the record from default
 * lists; restore clears the stamp.
 */
class ArchiveController extends Controller
{
    /** Whitelist of archivable record types (URL segment → model). */
    private const TYPES = [
        'enrollments' => Enrollment::class,
        'mock-test-enrollments' => MockTestEnrollment::class,
        'demo-requests' => DemoRequest::class,
        'exam-bookings' => ExamBookingEnrollment::class,
        'contact-messages' => ContactMessage::class,
    ];

    public function archive(Request $request, string $type, int $id): JsonResponse
    {
        return $this->setArchived($request, $type, $id, true);
    }

    public function restore(Request $request, string $type, int $id): JsonResponse
    {
        return $this->setArchived($request, $type, $id, false);
    }

    private function setArchived(Request $request, string $type, int $id, bool $archived): JsonResponse
    {
        $this->authorizeAdmin($request);

        $model = self::TYPES[$type] ?? null;
        if (! $model) {
            abort(Response::HTTP_NOT_FOUND, 'Unknown record type.');
        }

        $record = $model::findOrFail($id);
        $record->forceFill(['archived_at' => $archived ? now() : null])->save();

        // Group booking: members follow the leader in and out of the archive
        if ($model === Enrollment::class) {
            Enrollment::where('parent_enrollment_id', $record->id)
                ->update(['archived_at' => $archived ? now() : null]);

            // Sync invoice soft deletion
            if ($record->invoice_id) {
                $invoice = Invoice::withTrashed()->find($record->invoice_id);
                if ($invoice) {
                    if ($archived) {
                        $invoice->delete();
                    } else {
                        $invoice->restore();
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $archived
                ? 'Record archived. It is hidden from lists but can be restored anytime.'
                : 'Record restored.',
            'data' => $record->fresh(),
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['Super Admin', 'Admin']) && ! $user->can('manage_all'))) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can archive or restore records.');
        }
    }
}
