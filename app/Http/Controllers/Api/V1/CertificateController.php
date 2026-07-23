<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\CertificateGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CertificateController extends Controller
{
    public function __construct(private CertificateGenerator $generator) {}

    /** Stream a student's completion certificate as a PDF download. */
    public function download(Request $request, Enrollment $enrollment): SymfonyResponse
    {
        $user = $request->user();
        $canManageAll = $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all');

        if (! $canManageAll && $enrollment->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this certificate.');
        }

        if (! $enrollment->isCertificateEligible()) {
            abort(Response::HTTP_FORBIDDEN, 'This certificate is not available yet.');
        }

        $enrollment->loadMissing('user', 'batch.course');
        $pdf = $this->generator->pdf($enrollment);
        $filename = 'KTM-Certificate-'.Str::slug($this->generator->data($enrollment)['name']).'.pdf';

        return response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($pdf),
        ]);
    }
}
