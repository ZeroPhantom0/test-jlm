<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Approved</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 36px 32px; text-align: center;">
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 32px;">✓</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">Payment Approved</h1>
            <p style="color: #ecfdf5; margin: 8px 0 0; font-size: 14px;">JLM Cozy Cabin</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 8px;">Hi <strong>{{ $invoice->tenant_name }}</strong>,</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                Great news! Your payment has been <strong style="color: #10b981;">verified and approved</strong>. Your account has been updated accordingly.
            </p>

            {{-- Payment Details Card --}}
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #166534; font-size: 13px; font-weight: 700; margin: 0 0 16px; text-transform: uppercase; letter-spacing: 0.5px;">Payment Details</h3>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Invoice No.</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $invoice->transaction_no }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Description</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $invoice->description }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Room</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $invoice->room_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Payment Method</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $invoice->payment_method }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #bbf7d0;">
                        <td style="padding: 12px 0 0; color: #166534; font-size: 14px; font-weight: 700;">Amount Paid</td>
                        <td style="padding: 12px 0 0; color: #10b981; font-size: 18px; font-weight: 700; text-align: right;">₱{{ number_format((float) $invoice->amount_paid, 2) }}</td>
                    </tr>
                    @if($invoice->balance > 0)
                    <tr>
                        <td style="padding: 8px 0 0; color: #64748b; font-size: 13px;">Remaining Balance</td>
                        <td style="padding: 8px 0 0; color: #f59e0b; font-size: 13px; font-weight: 600; text-align: right;">₱{{ number_format((float) $invoice->balance, 2) }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            {{-- Status Badge --}}
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="display: inline-block; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; padding: 8px 20px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Status: {{ $invoice->status }}
                </span>
            </div>

            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 8px;">
                You can view and download your official receipt by logging into your tenant portal.
            </p>
            <p style="color: #94a3b8; font-size: 13px; margin: 0;">
                Thank you for your prompt payment!
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