<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #02122c 0%, #0d2347 100%); padding: 32px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 600;">Security Verification</h1>
        </div>
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 16px;">Hi {{ $userName }},</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                You requested to update your <strong>{{ $actionType }}</strong>. Please use the code below to confirm this change. This code expires in <strong>15 minutes</strong>.
            </p>
            <div style="background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px;">
                <span style="font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #0f766e; font-family: monospace;">{{ $code }}</span>
            </div>
            <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0;">
                If you did not request this change, please secure your account immediately.
            </p>
        </div>
    </div>
</body>
</html>