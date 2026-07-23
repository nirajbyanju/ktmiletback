<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>KTM Test Preparation Centre</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 12px;">
    <tr><td align="center">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;">

        {{-- Header: blue band, logo on white badge (Option 2) --}}
        <tr><td style="background-color:#1F4E79;padding:18px 28px;">
          <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            <td style="background:#ffffff;border-radius:12px;padding:5px 7px;" valign="middle">
              <img src="{{ $siteUrl }}/ktm-logo.jpg" alt="KTM" height="46" style="display:block;height:46px;width:auto;">
            </td>
            <td style="padding-left:14px;" valign="middle">
              <div style="font-size:17px;font-weight:800;color:#ffffff;line-height:1.3;">
                KTM <span style="color:#E9A23B;">Test Preparation Centre</span>
              </div>
              <div style="font-size:10.5px;font-weight:700;color:rgba(255,255,255,0.7);letter-spacing:0.08em;text-transform:uppercase;margin-top:3px;">
                A service of KTM Educational Consultancy Pvt. Ltd.
              </div>
            </td>
          </tr></table>
        </td></tr>

        {{-- Body --}}
        <tr><td style="padding:26px 28px 8px;">
          <div style="font-size:14px;line-height:1.75;color:#334155;white-space:pre-line;">{{ $bodyText }}</div>
        </td></tr>

        {{-- CTA button --}}
        @if (!empty($ctaText))
        <tr><td style="padding:16px 28px 24px;">
          <a href="{{ $ctaUrl }}"
             style="display:block;background-color:#E9A23B;color:#ffffff;text-align:center;font-weight:800;font-size:15px;padding:14px;border-radius:10px;text-decoration:none;">
            {{ $ctaText }} &rarr;
          </a>
        </td></tr>
        @endif

        {{-- Footer --}}
        <tr><td style="border-top:1px solid #e2e8f0;padding:16px 28px 22px;text-align:center;font-size:11.5px;color:#94a3b8;line-height:1.8;">
          <b style="color:#64748b;">KTM Test Preparation Centre</b> — a service of <b style="color:#64748b;">KTM Educational Consultancy Pvt. Ltd.</b><br>
          Putalisadak, Kathmandu, Nepal &middot; Phone +977 14526263 &middot; Mobile +977 9747469800<br>
          <a href="mailto:ktmtestpreparation@ktmeducational.edu.np" style="color:#1F4E79;text-decoration:none;">ktmtestpreparation@ktmeducational.edu.np</a>
          &middot; <a href="{{ $siteUrl }}" style="color:#1F4E79;text-decoration:none;">www.ktmtestpreparation.com</a>
          &middot; <a href="https://www.ktmeducational.edu.np" style="color:#1F4E79;text-decoration:none;">www.ktmeducational.edu.np</a>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
