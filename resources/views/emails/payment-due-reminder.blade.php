<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Due Reminder</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 36px 32px; text-align: center;">
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 32px;">⏰</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">Payment Reminder</h1>
            <p style="color: #fffbeb; margin: 8px 0 0; font-size: 14px;">Your payment is due soon</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 8px;">Hi <strong>{{ $invoice->tenant_name }}</strong>,</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                This is a friendly reminder that your payment is due in <strong style="color: #f59e0b;">{{ $daysUntilDue }} day{{ $daysUntilDue > 1 ? 's' : '' }}</strong>. Please ensure timely payment to avoid any late fees.
            </p>

            {{-- Due Date Highlight --}}
            <div style="background: #fffbeb; border: 2px solid #fcd34d; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px;">
                <p style="color: #92400e; font-size: 12px; font-weight: 700; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 1px;">Due Date</p>
                <p style="color: #b45309; font-size: 28px; font-weight: 700; margin: 0;">{{ $invoice->due_date ? $invoice->due_date->format('F j, Y') : 'N/A' }}</p>
            </div>

            {{-- Invoice Details Card --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #475569; font-size: 13px; font-weight: 700; margin: 0 0 16px; text-transform: uppercase; letter-spacing: 0.5px;">Invoice Details</h3>
                
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
                    <tr style="border-top: 1px solid #e2e8f0;">
                        <td style="padding: 12px 0 0; color: #475569; font-size: 14px; font-weight: 700;">Amount Due</td>
                        <td style="padding: 12px 0 0; color: #f59e0b; font-size: 18px; font-weight: 700; text-align: right;">₱{{ number_format((float) $invoice->balance, 2) }}</td>
                    </tr>
                </table>
            </div>

            {{-- Payment Methods --}}
            <div style="background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #0f766e; font-size: 13px; font-weight: 700; margin: 0 0 12px;">Accepted Payment Methods</h3>
                <ul style="color: #115e59; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
                    <li><strong>GCash:</strong> 0917-123-4567</li>
                    <li><strong>Bank Transfer:</strong> Contact office for details</li>
                    <li><strong>Over the Counter:</strong> Visit the management office</li>
                </ul>
            </div>

            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0;">
                Log into your <strong>tenant portal</strong> to submit your payment and upload your receipt.
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