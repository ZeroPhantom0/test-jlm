<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Approved</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 36px 32px; text-align: center;">
            {{-- Text-based Centered Icon --}}
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; line-height: 64px; text-align: center;">
                <span style="color: #ffffff; font-size: 32px; font-weight: 300; line-height: 1; display: inline-block; vertical-align: middle;">&#10003;</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">Reservation Approved!</h1>
            <p style="color: #ecfdf5; margin: 8px 0 0; font-size: 14px;">JLM Cozy Cabin</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 8px;">Hi <strong>{{ $reservation->tenant_name }}</strong>,</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                Great news! Your reservation request has been reviewed and <strong style="color: #10b981;">approved</strong>. We are excited to welcome you to JLM Cozy Cabin.
            </p>

            {{-- Details Card --}}
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #166534; font-size: 13px; font-weight: 700; margin: 0 0 16px; text-transform: uppercase; letter-spacing: 0.5px;">Reservation Details</h3>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Reference No.</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $reservation->reference_no }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Room</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $reservation->desired_room }} {{ $reservation->room_bed_id ? '('.$reservation->room_bed_id.')' : '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Check-in</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $reservation->check_in?->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Check-out</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $reservation->check_out?->format('M d, Y') ?? 'Open-ended' }}</td>
                    </tr>
                </table>
            </div>

            @if($reservation->admin_notes)
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #475569; font-size: 13px; font-weight: 700; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.5px;">Note from Admin</h3>
                <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0;">{{ $reservation->admin_notes }}</p>
            </div>
            @endif

            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 8px;">
                Please log into your tenant portal to view your updated billing details and any remaining balance.
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