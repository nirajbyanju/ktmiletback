<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>

<body style="margin:0; padding:0; background-color:#f5f7fa; font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:20px 10px;">
<tr>
<td align="center">

<table width="100%" max-width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05);">

    <tr>
        <td align="center" style="background:#123c7c; padding:25px; color:#ffffff; font-size:20px; font-weight:700;">
            KTM Test Preparation Centre
        </td>
    </tr>

    <tr>
        <td style="padding:35px 30px; color:#333; line-height:1.6;">

            <h2 style="margin:0 0 10px; font-size:22px; color:#123c7c;">
                Reset Your Password
            </h2>

            <p style="margin:0 0 15px;">
                Hello <strong>{{ $user->display_name ?? 'User' }}</strong>,
            </p>

            <p style="margin:0 0 20px;">
                We received a request to reset your password. Click the button below to set a new password.
            </p>

            <div style="text-align:center; margin:30px 0;">
                <a href="{{ $url }}"
                   style="
                        background:#c82127;
                        color:#ffffff;
                        padding:14px 30px;
                        text-decoration:none;
                        border-radius:6px;
                        font-weight:600;
                        display:inline-block;
                        font-size:14px;
                   ">
                    Reset Password
                </a>
            </div>

            <p style="margin:0 0 10px;">
                This link will expire in <strong>{{ $expiresInMinutes }} minutes</strong>.
            </p>

            <p style="margin:15px 0; font-size:13px; color:#555;">
                If the button does not work, copy and paste this link into your browser:
            </p>

            <p style="word-break:break-all; font-size:13px; color:#123c7c;">
                {{ $url }}
            </p>

            <p style="margin-top:25px; font-size:14px;">
                If you did not request this, you can safely ignore this email.
            </p>

            <p style="margin-top:30px;">
                Regards,<br>
                <strong style="color:#123c7c;">KTM Test Preparation Centre Team</strong>
            </p>

        </td>
    </tr>

    <tr>
        <td style="background:#f1f3f5; text-align:center; padding:18px; font-size:12px; color:#777;">
            &copy; {{ date('Y') }} KTM Test Preparation Centre. All rights reserved.
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
