<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Verification Code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); padding: 32px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 600;">JLM Cozy Cabin</h1>
        </div>
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 16px;">Hi {{ $userName }},</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                Use the code below to complete your sign-in. This code expires in <strong>{{ $expiresIn }}</strong>.
            </p>
            <div style="background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px;">
                <span style="font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #0f766e; font-family: monospace;">{{ $code }}</span>
            </div>
            <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0;">
                If you didn't attempt to sign in, please secure your account immediately by changing your password.
            </p>
        </div>
    </div>
</body>
</html>