@extends('emails.layout', [
    'emailSubject'   => 'Reset Your Password — KTM Test Preparation Centre',
    'recipientEmail' => $user->email ?? '',
])

@section('content')

@php
    $firstName  = $user->first_name ?? '';
    $lastName   = $user->last_name  ?? '';
    $initials   = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    if (!$initials) $initials = strtoupper(substr($user->email ?? 'U', 0, 2));
    $avatarColors = ['#2797dc','#1a7abf','#f05b6b','#e04455','#1a2f5a'];
    $avatarBg   = $avatarColors[crc32($user->email ?? '') % count($avatarColors)];
@endphp

<!-- ── PAGE TITLE ── -->
<h1 style="margin:0 0 4px;font-size:26px;font-weight:800;color:#1a2f5a;line-height:1.2;">
  Reset Your Password
</h1>
<p style="margin:0 0 28px;font-size:13px;font-weight:600;color:#f05b6b;
           text-transform:uppercase;letter-spacing:1px;">
  Security · Password Reset
</p>

<!-- ── USER PROFILE CARD ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background:#f0f7ff;border:1px solid #bee3f8;border-radius:12px;
              margin:0 0 24px;overflow:hidden;">
  <tr>
    <td width="88" valign="top" style="padding:22px 0 22px 24px;">
      <div style="width:64px;height:64px;border-radius:50%;background:{{ $avatarBg }};
                  font-size:22px;font-weight:800;color:#ffffff;text-align:center;
                  line-height:64px;font-family:Arial,sans-serif;">
        {{ $initials }}
      </div>
    </td>
    <td valign="top" style="padding:22px 24px 20px 16px;">
      <p style="margin:0 0 2px;font-size:17px;font-weight:800;color:#1a2f5a;">
        {{ $user->display_name ?? 'User' }}
      </p>
      <p style="margin:0 0 8px;font-size:13px;color:#2797dc;font-weight:600;">
        {{ $user->email ?? '' }}
      </p>
      <span style="display:inline-block;background:#fef3c7;color:#d97706;
                    font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
                    text-transform:uppercase;letter-spacing:0.5px;">
        ⚠ Password Reset Requested
      </span>
    </td>
  </tr>
</table>

<!-- ── BODY TEXT ── -->
<p style="margin:0 0 16px;font-size:15px;color:#2d3748;">
  Hello <strong>{{ $user->first_name ?? 'there' }}</strong>,
</p>
<p style="margin:0 0 20px;font-size:15px;color:#4a5568;line-height:1.8;">
  We received a request to reset the password for your
  <strong style="color:#1a2f5a;">KTM Test Preparation Centre</strong> account.
  Click the button below to create a new password.
</p>

<!-- ── CTA BUTTON ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="margin:0 0 24px;">
  <tr>
    <td align="center">
      <a href="{{ $url }}"
         style="display:inline-block;background:#f05b6b;color:#ffffff;font-size:15px;
                font-weight:800;padding:15px 44px;border-radius:10px;text-decoration:none;
                letter-spacing:0.3px;box-shadow:0 4px 12px rgba(240,91,107,0.35);">
        Reset My Password →
      </a>
    </td>
  </tr>
</table>

<!-- ── EXPIRY WARNING ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="margin:0 0 20px;">
  <tr>
    <td style="background:#fffbf0;border:1px solid #fde68a;border-left:4px solid #f59e0b;
                border-radius:8px;padding:14px 18px;">
      <p style="margin:0;font-size:13px;color:#92400e;font-weight:600;">
        ⏱ This link expires in <strong>{{ $expiresInMinutes }} minutes</strong>.
        Request a new one if it has expired.
      </p>
    </td>
  </tr>
</table>

<!-- ── FALLBACK LINK ── -->
<p style="margin:0 0 6px;font-size:13px;color:#718096;">
  If the button doesn't work, copy and paste this link into your browser:
</p>
<p style="margin:0 0 24px;word-break:break-all;font-size:12px;color:#2797dc;
           background:#f0f7ff;padding:10px 14px;border-radius:6px;border:1px solid #bee3f8;">
  {{ $url }}
</p>

<!-- ── SECURITY NOTICE ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="margin:0 0 8px;">
  <tr>
    <td style="background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #22c55e;
                border-radius:8px;padding:14px 18px;">
      <p style="margin:0;font-size:13px;color:#166534;">
        🔒 If you did not request a password reset, your account is safe.
        Simply ignore this email — no changes will be made.
      </p>
    </td>
  </tr>
</table>

<p style="margin:28px 0 0;font-size:14px;color:#4a5568;">
  Regards,<br/>
  <strong style="color:#1a2f5a;font-size:15px;">KTM Test Preparation Centre Team</strong><br/>
  <span style="font-size:12px;color:#a0aec0;">Security &amp; Account Services</span>
</p>

@endsection
