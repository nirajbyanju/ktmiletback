@extends('emails.layout', [
    'emailSubject'   => 'Welcome to KTM Test Preparation Centre!',
    'recipientEmail' => $user->email ?? '',
])

@section('content')

@php
    // Build initials avatar
    $firstName  = $user->first_name ?? '';
    $lastName   = $user->last_name  ?? '';
    $initials   = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    if (!$initials) $initials = strtoupper(substr($user->email ?? 'U', 0, 2));
    $avatarColors = ['#2797dc','#1a7abf','#f05b6b','#e04455','#1a2f5a'];
    $avatarBg   = $avatarColors[crc32($user->email ?? '') % count($avatarColors)];
    $frontendUrl = rtrim(config('app.frontend_url', 'http://127.0.0.1:3000'), '/');
@endphp

<!-- ── PAGE TITLE ── -->
<h1 style="margin:0 0 4px;font-size:26px;font-weight:800;color:#1a2f5a;line-height:1.2;">
  Welcome to KTM! 🎉
</h1>
<p style="margin:0 0 28px;font-size:13px;font-weight:600;color:#f05b6b;
           text-transform:uppercase;letter-spacing:1px;">
  Your account is ready
</p>

<!-- ── USER PROFILE CARD ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background:#f0f7ff;border:1px solid #bee3f8;border-radius:12px;
              margin:0 0 28px;overflow:hidden;">
  <tr>
    <!-- Avatar column -->
    <td width="88" valign="top" style="padding:24px 0 24px 24px;">
      <div style="width:68px;height:68px;border-radius:50%;background:{{ $avatarBg }};
                  font-size:24px;font-weight:800;color:#ffffff;text-align:center;
                  line-height:68px;font-family:Arial,sans-serif;letter-spacing:1px;">
        {{ $initials }}
      </div>
    </td>
    <!-- Details column -->
    <td valign="top" style="padding:24px 24px 20px 16px;">
      <p style="margin:0 0 2px;font-size:18px;font-weight:800;color:#1a2f5a;">
        {{ $user->display_name ?? 'User' }}
      </p>
      <p style="margin:0 0 12px;font-size:13px;color:#2797dc;font-weight:600;">
        {{ $user->email ?? '' }}
      </p>
      <!-- Detail rows -->
      @if($user->phone)
      <p style="margin:0 0 5px;font-size:12px;color:#4a5568;">
        📞 &nbsp;<span style="color:#718096;">Phone:</span>&nbsp;
        <strong>{{ $user->phone }}</strong>
      </p>
      @endif
      <p style="margin:0 0 5px;font-size:12px;color:#4a5568;">
        🪪 &nbsp;<span style="color:#718096;">User Code:</span>&nbsp;
        <strong style="background:#e8f4ff;color:#2797dc;padding:2px 8px;border-radius:4px;
                        font-family:monospace;font-size:12px;">
          {{ $user->userCode ?? '—' }}
        </strong>
      </p>
      <p style="margin:4px 0 0;">
        <span style="display:inline-block;background:#dcfce7;color:#16a34a;
                      font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
                      text-transform:uppercase;letter-spacing:0.5px;">
          ✓ Active Account
        </span>
      </p>
    </td>
  </tr>
</table>

<!-- ── GREETING ── -->
<p style="margin:0 0 16px;font-size:15px;color:#2d3748;">
  Hello <strong>{{ $user->first_name ?? 'there' }}</strong>,
</p>
<p style="margin:0 0 24px;font-size:15px;color:#4a5568;line-height:1.8;">
  Thank you for joining <strong style="color:#1a2f5a;">KTM Test Preparation Centre</strong>!
  Your account is now active and ready to use. We're excited to support your journey to
  achieving your target score in IELTS, PTE, and English language training.
</p>

<!-- ── WHAT'S NEXT ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background:#fffbf0;border:1px solid #fde68a;border-radius:12px;
              margin:0 0 28px;overflow:hidden;">
  <tr>
    <td style="padding:20px 24px;">
      <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#d97706;
                 text-transform:uppercase;letter-spacing:1.5px;">
        What's Next
      </p>
      @foreach([
        ['✅', 'Explore our IELTS & PTE preparation courses'],
        ['📅', 'Book your exam slot online — quick and easy'],
        ['📊', 'Track your progress from your personal dashboard'],
        ['💬', 'Get expert guidance from our experienced team'],
        ['🏆', 'Join hundreds of students who achieved band 7+'],
      ] as [$icon, $text])
      <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
        <tr>
          <td width="28" valign="top"
              style="padding:5px 0;font-size:14px;color:#374151;">{{ $icon }}</td>
          <td style="padding:5px 0;font-size:14px;color:#374151;">{{ $text }}</td>
        </tr>
      </table>
      @endforeach
    </td>
  </tr>
</table>

<!-- ── CTA BUTTON ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="margin:0 0 28px;">
  <tr>
    <td align="center">
      <a href="{{ $frontendUrl }}/login"
         style="display:inline-block;background:#2797dc;color:#ffffff;font-size:15px;
                font-weight:800;padding:15px 44px;border-radius:10px;text-decoration:none;
                letter-spacing:0.3px;box-shadow:0 4px 12px rgba(39,151,220,0.35);">
        Go to My Dashboard →
      </a>
    </td>
  </tr>
</table>

<!-- ── SUPPORT NOTE ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
              margin:0 0 8px;">
  <tr>
    <td style="padding:16px 20px;">
      <p style="margin:0;font-size:13px;color:#4a5568;line-height:1.7;">
        💡 Need help? Contact us at
        <a href="mailto:onlineclass@ktmeducational.edu.np"
           style="color:#2797dc;text-decoration:none;font-weight:600;">
          onlineclass@ktmeducational.edu.np
        </a>
        — we typically respond within 24 hours.
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
