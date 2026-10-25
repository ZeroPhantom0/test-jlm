<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Payment Notice</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 36px 32px; text-align: center;">
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 32px;">⚠️</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">Overdue Payment Notice</h1>
            <p style="color: #fef2f2; margin: 8px 0 0; font-size: 14px;">Immediate action required</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 8px;">Hi <strong>{{ $invoice->tenant_name }}</strong>,</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                Our records show that your payment is now <strong style="color: #dc2626;">{{ $daysOverdue }} day{{ $daysOverdue > 1 ? 's' : '' }} overdue</strong>. Please settle your balance immediately to avoid additional fees or service interruption.
            </p>

            {{-- Overdue Alert Box --}}
            <div style="background: #fef2f2; border: 2px solid #fca5a5; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px;">
                <p style="color: #991b1b; font-size: 12px; font-weight: 700; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px;">Overdue Since</p>
                <p style="color: #b91c1c; font-size: 24px; font-weight: 700; margin: 0 0 16px;">{{ $invoice->due_date ? $invoice->due_date->format('F j, Y') : 'N/A' }}</p>
                <div style="border-top: 1px solid #fecaca; padding-top: 16px;">
                    <p style="color: #991b1b; font-size: 12px; font-weight: 700; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px;">Amount Overdue</p>
                    <p style="color: #dc2626; font-size: 32px; font-weight: 800; margin: 0;">₱{{ number_format((float) $invoice->balance, 2) }}</p>
                </div>
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
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Total Amount</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">₱{{ number_format((float) $invoice->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Amount Paid</td>
                        <td style="padding: 8px 0; color: #10b981; font-size: 13px; font-weight: 600; text-align: right;">₱{{ number_format((float) $invoice->amount_paid, 2) }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #e2e8f0;">
                        <td style="padding: 12px 0 0; color: #991b1b; font-size: 14px; font-weight: 700;">Balance Due</td>
                        <td style="padding: 12px 0 0; color: #dc2626; font-size: 18px; font-weight: 700; text-align: right;">₱{{ number_format((float) $invoice->balance, 2) }}</td>
                    </tr>
                </table>
            </div>

            {{-- Action Required --}}
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #991b1b; font-size: 13px; font-weight: 700; margin: 0 0 12px;">Action Required:</h3>
                <ol style="color: #7f1d1d; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
                    <li>Log into your tenant portal immediately</li>
                    <li>Navigate to the Billing section</li>
                    <li>Submit your payment with proof of payment</li>
                    <li>Wait for admin verification</li>
                </ol>
            </div>

            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 8px;">
                If you have already made a payment, please disregard this notice. For questions or concerns, contact the management office immediately.
            </p>
            <p style="color: #94a3b8; font-size: 13px; margin: 0;">
                Failure to settle your balance may result in additional penalties.
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