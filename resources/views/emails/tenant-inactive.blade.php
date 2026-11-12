<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your account is now inactive</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 36px 32px; text-align: center;">
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; line-height: 64px;">
                <span style="font-size: 30px;">🕒</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">We miss you!</h1>
            <p style="color: #fef3c7; margin: 8px 0 0; font-size: 14px;">JLM Cozy Cabin — Account Notice</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 8px;">Hi <strong>{{ $userName }}</strong>,</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 20px;">
                We noticed that you haven't made a reservation with JLM Cozy Cabin in the last <strong>six (6) months</strong>.
                As stated in our Privacy Policy, your tenant account status has been changed to
                <strong style="color: #d97706;">Inactive</strong>.
            </p>

            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #92400e; font-size: 13px; font-weight: 700; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 0.5px;">What this means</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; color: #78350f; font-size: 13px;">Last reservation activity</td>
                        <td style="padding: 6px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $lastActivity }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #78350f; font-size: 13px;">Can you still log in?</td>
                        <td style="padding: 6px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">Yes</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #78350f; font-size: 13px;">How to reactivate</td>
                        <td style="padding: 6px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">Make a new reservation</td>
                    </tr>
                </table>
            </div>

            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                Your account will become <strong>Active</strong> again as soon as a new reservation is approved.
                Accounts that stay inactive for twelve (12) months may be deactivated, as described in our Privacy Policy.
            </p>

            {{-- CTA --}}
            <div style="text-align: center; margin-bottom: 24px;">
                <a href="{{ $reserveUrl }}" style="display: inline-block; background: #d97706; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 10px;">
                    Browse Available Rooms
                </a>
            </div>

            <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0;">
                If you believe this is a mistake or need help, please contact the JLM Cozy Cabin management office.
            </p>
        </div>

        {{-- Footer --}}
        <div style="background: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="color: #94a3b8; font-size: 12px; margin: 0;">
                This is an automated message from JLM Cozy Cabin. Please do not reply directly to this email.
            </p>
        </div>
    </div>
</body>
</html>
