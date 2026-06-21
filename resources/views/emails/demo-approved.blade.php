@extends('emails.layout', [
    'emailSubject'   => 'Your Live Demo Class is Confirmed — KTM Test Prep',
    'recipientEmail' => $demoRequest->email ?? '',
])

@section('content')

@php
    $frontendUrl = rtrim(config('app.frontend_url', 'http://127.0.0.1:3000'), '/');
    $scheduledAt = $demoRequest->scheduled_at;
    $formattedDate = $scheduledAt ? $scheduledAt->format('D, d M Y \a\t h:i A') : null;
@endphp

<!-- PAGE TITLE -->
<h1 style="margin:0 0 4px;font-size:26px;font-weight:800;color:#1a2f5a;line-height:1.2;">
  ✓ Your Live Demo is Confirmed!
</h1>
<p style="margin:0 0 28px;font-size:13px;font-weight:600;color:#16a34a;
           text-transform:uppercase;letter-spacing:1px;">
  Session details inside
</p>

<!-- GREETING -->
<p style="margin:0 0 16px;font-size:15px;color:#2d3748;">
  Dear <strong>{{ $demoRequest->name }}</strong>,
</p>
<p style="margin:0 0 24px;font-size:15px;color:#4a5568;line-height:1.8;">
  Great news! Your request for a live demo class has been confirmed.
  Here are your session details — please save this information.
</p>

<!-- SESSION DETAILS CARD -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background:#f0f7ff;border:1px solid #bee3f8;border-radius:12px;
              margin:0 0 28px;overflow:hidden;">
  <tr>
    <td style="padding:24px;">
      <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#2797dc;
                 text-transform:uppercase;letter-spacing:1.5px;">
        Session Details
      </p>

      <!-- Course -->
      @if($demoRequest->course_name)
      <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
        <tr>
          <td width="24" valign="top" style="padding:5px 0;font-size:14px;">📚</td>
          <td style="padding:5px 0;font-size:14px;color:#374151;">
            <strong>Course:</strong> {{ $demoRequest->course_name }}
          </td>
        </tr>
      </table>
      @endif

      <!-- Education Level -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
        <tr>
          <td width="24" valign="top" style="padding:5px 0;font-size:14px;">🎓</td>
          <td style="padding:5px 0;font-size:14px;color:#374151;">
            <strong>Education Level:</strong> {{ $demoRequest->education_level }}
          </td>
        </tr>
      </table>

      <!-- Date & Time -->
      @if($formattedDate)
      <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
        <tr>
          <td width="24" valign="top" style="padding:5px 0;font-size:14px;">📅</td>
          <td style="padding:5px 0;font-size:14px;color:#374151;">
            <strong>Date &amp; Time:</strong> {{ $formattedDate }}
          </td>
        </tr>
      </table>
      @endif

      <!-- Zoom Link -->
      @if($demoRequest->zoom_url)
      <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
        <tr>
          <td width="24" valign="top" style="padding:5px 0;font-size:14px;">🔗</td>
          <td style="padding:5px 0;font-size:14px;color:#374151;">
            <strong>Meeting Link:</strong>
            <a href="{{ $demoRequest->zoom_url }}"
               style="color:#2797dc;text-decoration:none;font-weight:600;
                      word-break:break-all;">
              {{ $demoRequest->zoom_url }}
            </a>
          </td>
        </tr>
      </table>
      @endif
    </td>
  </tr>
</table>

<!-- JOIN BUTTON -->
@if($demoRequest->zoom_url)
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="margin:0 0 28px;">
  <tr>
    <td align="center">
      <a href="{{ $demoRequest->zoom_url }}"
         style="display:inline-block;background:#2797dc;color:#ffffff;font-size:15px;
                font-weight:800;padding:15px 44px;border-radius:10px;text-decoration:none;
                letter-spacing:0.3px;box-shadow:0 4px 12px rgba(39,151,220,0.35);">
        Join Zoom Meeting →
      </a>
    </td>
  </tr>
</table>
@endif

<!-- ADMIN NOTES -->
@if($demoRequest->admin_notes)
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background:#fffbf0;border:1px solid #fde68a;border-radius:12px;
              margin:0 0 24px;">
  <tr>
    <td style="padding:16px 20px;">
      <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#d97706;
                 text-transform:uppercase;letter-spacing:1.5px;">
        Note from our team
      </p>
      <p style="margin:0;font-size:14px;color:#374151;line-height:1.7;">
        {{ $demoRequest->admin_notes }}
      </p>
    </td>
  </tr>
</table>
@endif

<!-- TIPS -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
              margin:0 0 24px;">
  <tr>
    <td style="padding:16px 20px;">
      <p style="margin:0 0 10px;font-size:13px;font-weight:700;color:#1a2f5a;">
        Tips for your demo session:
      </p>
      @foreach([
        'Join 5 minutes early to test your audio and video',
        'Have a stable internet connection ready',
        'Prepare any questions you want to ask the teacher',
        'A notepad is helpful for taking notes',
      ] as $tip)
      <p style="margin:0 0 6px;font-size:13px;color:#4a5568;">✔ {{ $tip }}</p>
      @endforeach
    </td>
  </tr>
</table>

<!-- SUPPORT NOTE -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="margin:0 0 8px;">
  <tr>
    <td style="padding:0;">
      <p style="margin:0;font-size:13px;color:#4a5568;line-height:1.7;">
        💡 Questions? Contact us at
        <a href="mailto:onlineclass@ktmeducational.edu.np"
           style="color:#2797dc;text-decoration:none;font-weight:600;">
          onlineclass@ktmeducational.edu.np
        </a>
        — we respond within a few hours.
      </p>
    </td>
  </tr>
</table>

<p style="margin:24px 0 0;font-size:14px;color:#4a5568;">
  Warm regards,<br/>
  <strong style="color:#1a2f5a;font-size:15px;">KTM Test Preparation Centre Team</strong><br/>
  <span style="font-size:12px;color:#a0aec0;">Empowering students since 2010</span>
</p>

@endsection
