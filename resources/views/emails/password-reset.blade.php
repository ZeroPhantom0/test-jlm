<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); padding: 36px 32px; text-align: center;">
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 32px;">🔐</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">Password Reset Request</h1>
            <p style="color: #e0e7ff; margin: 8px 0 0; font-size: 14px;">JLM Cozy Cabin</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 8px;">Hi <strong>{{ $userName }}</strong>,</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                We received a request to reset the password for your JLM Cozy Cabin account. Click the button below to choose a new password.
            </p>

            {{-- Reset Button --}}
            <div style="text-align: center; margin-bottom: 24px;">
                <a href="{{ $resetUrl }}" style="display: inline-block; background: #4f46e5; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 14px 32px; border-radius: 10px;">
                    Reset My Password
                </a>
            </div>

            {{-- Expiry Note --}}
            <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; text-align: center;">
                <p style="color: #3730a3; font-size: 12px; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Expires In</p>
                <p style="color: #4f46e5; font-size: 20px; font-weight: 800; margin: 0;">{{ $expiresIn }}</p>
            </div>

            {{-- Fallback URL --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                <p style="color: #64748b; font-size: 12px; margin: 0 0 8px;">If the button doesn't work, copy and paste this link into your browser:</p>
                <p style="color: #4f46e5; font-size: 11px; word-break: break-all; margin: 0; font-family: monospace;">
                    {{ $resetUrl }}
                </p>
            </div>

            <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0;">
                If you didn't request a password reset, you can safely ignore this email — your password won't change until you create a new one.
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