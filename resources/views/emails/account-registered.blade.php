<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to JLM Cozy Cabin</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); padding: 36px 32px; text-align: center;">
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 32px;">🎉</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">Welcome Aboard!</h1>
            <p style="color: #e0e7ff; margin: 8px 0 0; font-size: 14px;">JLM Cozy Cabin — Tenant Portal</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 8px;">Hi <strong>{{ $userName }}</strong>,</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                Your account has been <strong style="color: #10b981;">successfully created</strong>. You can now log in to access the tenant portal and manage your reservations, payments, and services.
            </p>

            {{-- Account Card --}}
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #166534; font-size: 13px; font-weight: 700; margin: 0 0 16px; text-transform: uppercase; letter-spacing: 0.5px;">Your Account</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Full Name</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $userName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Email</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $userEmail }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Role</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">Tenant</td>
                    </tr>
                </table>
            </div>

            {{-- Next Steps --}}
            <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #3730a3; font-size: 13px; font-weight: 700; margin: 0 0 12px;">What's next?</h3>
                <ol style="color: #312e81; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
                    <li>Complete your personal information</li>
                    <li>Upload a valid ID for verification</li>
                    <li>Browse available rooms and reserve your space</li>
                    <li>Track your payments and service history</li>
                </ol>
            </div>

            {{-- CTA --}}
            <div style="text-align: center; margin-bottom: 24px;">
                <a href="{{ $loginUrl }}" style="display: inline-block; background: #4f46e5; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 10px;">
                    Log In to Your Portal
                </a>
            </div>

            <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0;">
                If you didn't create this account, please contact the management office immediately.
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