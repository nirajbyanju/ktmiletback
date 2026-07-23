<?php

namespace App\Services;

use App\Models\Enrollment;
use Carbon\Carbon;
use RuntimeException;

/**
 * Draws the KTM "Certificate of Attendance" for a student and returns it as a
 * PDF. The certificate artwork (border, logo, gold badge, the erased-and-blank
 * text areas) lives in resources/certificates/background.jpg; this service
 * paints the student's name, course, dates, the authorised signature and the
 * company seal onto it with PHP GD, then wraps the image in a one-page PDF.
 *
 * Coordinates below are expressed against a 900x600 design grid and scaled to
 * the artwork's real resolution, so the layout matches the approved preview.
 */
class CertificateGenerator
{
    private const NAVY = [27, 54, 93];

    private const DARK = [44, 62, 80];

    private const RED = [217, 35, 42];

    /** Return the finished certificate as PDF bytes. */
    public function pdf(Enrollment $enrollment): string
    {
        $jpeg = $this->jpeg($enrollment);
        $size = getimagesizefromstring($jpeg);

        return $this->wrapInPdf($jpeg, (int) $size[0], (int) $size[1]);
    }

    /**
     * The display values printed on the certificate.
     *
     * @return array{name:string, course:string, dates:string, issued:string}
     */
    public function data(Enrollment $enrollment): array
    {
        $user = $enrollment->user;
        $name = $user
            ? (trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? ''))
            : '';
        $name = $name ?: ($enrollment->student_name ?? 'Student');

        $course = $enrollment->batch?->course?->course_name
            ?: ($enrollment->batch?->batch_type ?? 'Preparation');

        $start = $enrollment->start_date ?? $enrollment->batch?->start_date;
        $end = $enrollment->end_date ?? $enrollment->batch?->end_date;
        $dates = ($start && $end)
            ? 'from '.$start->format('M j, Y').' to '.$end->format('M j, Y')
            : '';

        $issued = ($enrollment->certificate_sent_at ?? Carbon::now())->format('M j, Y');

        return [
            'name' => $name,
            'course' => $course,
            'dates' => $dates,
            'issued' => $issued,
        ];
    }

    /** Draw the certificate and return JPEG bytes. */
    public function jpeg(Enrollment $enrollment): string
    {
        if (! function_exists('imagettftext')) {
            throw new RuntimeException('GD with FreeType is required to generate certificates.');
        }

        $base = resource_path('certificates');
        $img = @imagecreatefromjpeg("$base/background.jpg");
        if (! $img) {
            throw new RuntimeException('Certificate background is missing.');
        }
        imagealphablending($img, true);

        $w = imagesx($img);
        $h = imagesy($img);
        $sx = $w / 900.0;
        $sy = $h / 600.0;

        $fonts = [
            'name' => "$base/fonts/DejaVuSerif-BoldItalic.ttf",
            'bold' => "$base/fonts/DejaVuSerif-Bold.ttf",
        ];

        $navy = imagecolorallocate($img, ...self::NAVY);
        $dark = imagecolorallocate($img, ...self::DARK);
        $red = imagecolorallocate($img, ...self::RED);

        $d = $this->data($enrollment);

        $this->centeredText($img, $fonts['name'], 33, 450, 348, $navy, $d['name'], $sx, $sy);

        $this->centeredSegments($img, [
            ['the ', $dark],
            [$d['course'], $red],
            [' Course conducted', $dark],
        ], $fonts['bold'], 17, 450, 401, $sx, $sy);

        if ($d['dates'] !== '') {
            $this->centeredText($img, $fonts['bold'], 14, 450, 424, $dark, $d['dates'], $sx, $sy);
        }

        $this->centeredText($img, $fonts['bold'], 14, 220, 461, $navy, $d['issued'], $sx, $sy);

        $this->placePng($img, "$base/signature.png", 445, 430, 90, $sx, $sy);
        $this->placePng($img, "$base/stamp.png", 694, 416, 126, $sx, $sy);

        $this->centeredText($img, $fonts['bold'], 12, 445, 503, $navy, 'Authorized Signature', $sx, $sy);
        $this->centeredText($img, $fonts['bold'], 12, 694, 503, $navy, 'Company Seal', $sx, $sy);

        ob_start();
        imagejpeg($img, null, 94);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    /** Draw horizontally-centred text at a baseline given in 900x600 grid units. */
    private function centeredText(\GdImage $img, string $font, float $size, float $x9, float $y9, int $color, string $text, float $sx, float $sy): void
    {
        $box = imagettfbbox($size, 0, $font, $text);
        $textWidth = $box[2] - $box[0];
        $x = $x9 * $sx - $textWidth / 2;
        imagettftext($img, $size, 0, (int) round($x), (int) round($y9 * $sy), $color, $font, $text);
    }

    /**
     * Draw a single centred line made of coloured segments (e.g. a red course
     * name inside dark framing words).
     *
     * @param  array<int, array{0:string, 1:int}>  $segments
     */
    private function centeredSegments(\GdImage $img, array $segments, string $font, float $size, float $x9, float $y9, float $sx, float $sy): void
    {
        $widths = [];
        $total = 0;
        foreach ($segments as $segment) {
            $box = imagettfbbox($size, 0, $font, $segment[0]);
            $width = $box[2] - $box[0];
            $widths[] = $width;
            $total += $width;
        }

        $x = $x9 * $sx - $total / 2;
        $y = (int) round($y9 * $sy);
        foreach ($segments as $i => $segment) {
            imagettftext($img, $size, 0, (int) round($x), $y, $segment[1], $font, $segment[0]);
            $x += $widths[$i];
        }
    }

    /** Overlay a transparent PNG, centred on cx9 with a target width, top at ty9. */
    private function placePng(\GdImage $dst, string $path, float $cx9, float $ty9, float $width9, float $sx, float $sy): void
    {
        $src = @imagecreatefrompng($path);
        if (! $src) {
            return;
        }
        imagealphablending($src, true);
        imagesavealpha($src, true);

        $sw = imagesx($src);
        $sh = imagesy($src);
        $targetW = (int) round($width9 * $sx);
        $targetH = (int) round($targetW * $sh / $sw);
        $x = (int) round($cx9 * $sx) - (int) ($targetW / 2);
        $y = (int) round($ty9 * $sy);

        imagecopyresampled($dst, $src, $x, $y, 0, 0, $targetW, $targetH, $sw, $sh);
        imagedestroy($src);
    }

    /**
     * Wrap a JPEG into a valid single-page PDF with no external dependencies.
     * The page keeps the image's aspect ratio on an A4-landscape-width canvas.
     */
    private function wrapInPdf(string $jpeg, int $wpx, int $hpx): string
    {
        $pageW = 842.0;
        $pageH = round($pageW * $hpx / $wpx, 2);
        $pw = round($pageW, 2);
        $content = "q $pw 0 0 $pageH 0 0 cm /Im0 Do Q";

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pw $pageH] "
                .'/Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>',
            4 => '<< /Type /XObject /Subtype /Image /Width '.$wpx.' /Height '.$hpx.' '
                .'/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode '
                .'/Length '.strlen($jpeg)." >>\nstream\n$jpeg\nendstream",
            5 => '<< /Length '.strlen($content)." >>\nstream\n$content\nendstream",
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "$number 0 obj\n$body\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $size = count($objects) + 1;
        $pdf .= "xref\n0 $size\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

        return $pdf;
    }
}
