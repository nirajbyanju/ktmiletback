@php
    $logoPath  = public_path('images/logo.png');
    $logoData  = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    $frontendUrl = rtrim(config('app.frontend_url', 'http://127.0.0.1:3000'), '/');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
  <title>{{ $emailSubject ?? 'KTM Test Preparation Centre' }}</title>
  <style>
    body{margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Helvetica,Arial,sans-serif}
    table{border-collapse:collapse}
    img{border:0;line-height:100%;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic}
    @media only screen and (max-width:640px){
      .email-card{width:100%!important;border-radius:0!important}
      .email-body{padding:24px 20px!important}
      .email-header{padding:24px 20px!important}
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="background:#eef2f7;padding:40px 16px 32px;">
  <tr><td align="center">

    <!-- ═══ EMAIL CARD ═══ -->
    <table class="email-card" width="600" cellpadding="0" cellspacing="0" border="0" role="presentation"
           style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;
                  box-shadow:0 8px 32px rgba(0,0,0,0.10);">

      <!-- ── TOP BAR (brand blue line) ── -->
      <tr>
        <td style="background:linear-gradient(90deg,#2797dc 0%,#1a7abf 100%);height:5px;font-size:0;line-height:0;">&nbsp;</td>
      </tr>

      <!-- ── HEADER ── -->
      <tr>
        <td class="email-header" align="center"
            style="background:#1a2f5a;padding:32px 40px 28px;">

          @if($logoData)
            <img src="{{ $logoData }}" alt="KTM Test Preparation Centre"
                 width="140" style="display:block;max-width:140px;height:auto;margin:0 auto;"/>
          @else
            <p style="margin:0;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:1px;">
              KTM TEST PREP
            </p>
          @endif

          <p style="margin:12px 0 0;font-size:10px;letter-spacing:3px;text-transform:uppercase;
                     color:rgba(255,255,255,0.55);font-weight:600;">
            KTM Test Preparation Centre
          </p>
        </td>
      </tr>

      <!-- ── ACCENT STRIP ── -->
      <tr>
        <td style="background:#f05b6b;height:3px;font-size:0;line-height:0;">&nbsp;</td>
      </tr>

      <!-- ── BODY ── -->
      <tr>
        <td class="email-body" style="padding:36px 44px 32px;color:#2d3748;font-size:15px;line-height:1.75;">
          @yield('content')
        </td>
      </tr>

      <!-- ── FOOTER ── -->
      <tr>
        <td style="background:#f7f9fc;border-top:1px solid #e8edf2;padding:28px 44px 32px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
            <tr>
              <td align="center">

                <!-- Social/site links -->
                <table cellpadding="0" cellspacing="0" border="0" role="presentation"
                       style="margin-bottom:18px;">
                  <tr>
                    <td style="padding:0 6px;">
                      <a href="{{ $frontendUrl }}"
                         style="display:inline-block;background:#2797dc;color:#fff;font-size:11px;
                                font-weight:700;padding:7px 18px;border-radius:20px;
                                text-decoration:none;letter-spacing:0.5px;">
                        Visit Website
                      </a>
                    </td>
                    <td style="padding:0 6px;">
                      <a href="mailto:onlineclass@ktmeducational.edu.np"
                         style="display:inline-block;background:#1a2f5a;color:#fff;font-size:11px;
                                font-weight:700;padding:7px 18px;border-radius:20px;
                                text-decoration:none;letter-spacing:0.5px;">
                        Contact Us
                      </a>
                    </td>
                  </tr>
                </table>

                <!-- Logo small -->
                @if($logoData)
                  <img src="{{ $logoData }}" alt="KTM" width="60"
                       style="display:block;margin:0 auto 12px;opacity:0.6;"/>
                @endif

                <p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#2797dc;">
                  KTM Test Preparation Centre
                </p>
                <p style="margin:0 0 4px;font-size:12px;color:#718096;">
                  IELTS &nbsp;&bull;&nbsp; PTE &nbsp;&bull;&nbsp; English Language Training
                </p>
                <p style="margin:0 0 14px;font-size:12px;color:#718096;">
                  Kathmandu, Nepal &nbsp;&bull;&nbsp;
                  <a href="mailto:onlineclass@ktmeducational.edu.np"
                     style="color:#2797dc;text-decoration:none;">
                    onlineclass@ktmeducational.edu.np
                  </a>
                </p>

                <p style="margin:0;font-size:11px;color:#a0aec0;line-height:1.7;">
                  &copy; {{ date('Y') }} KTM Test Preparation Centre. All rights reserved.<br/>
                  @if(!empty($recipientEmail))
                    This email was sent to
                    <a href="mailto:{{ $recipientEmail }}"
                       style="color:#a0aec0;text-decoration:none;">{{ $recipientEmail }}</a>.
                  @endif
                </p>

              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- ── BOTTOM BAR ── -->
      <tr>
        <td style="background:linear-gradient(90deg,#1a2f5a 0%,#2797dc 100%);height:4px;
                   font-size:0;line-height:0;">&nbsp;</td>
      </tr>

    </table>
    <!-- /EMAIL CARD -->

    <p style="margin:20px 0 0;font-size:11px;color:#a0aec0;text-align:center;">
      &copy; {{ date('Y') }} KTM Test Preparation Centre
    </p>

  </td></tr>
</table>

</body>
</html>
