<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Rejected</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 36px 32px; text-align: center;">
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 32px;">✗</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">Payment Rejected</h1>
            <p style="color: #fef2f2; margin: 8px 0 0; font-size: 14px;">JLM Cozy Cabin</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 8px;">Hi <strong>{{ $invoice->tenant_name }}</strong>,</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                Unfortunately, your payment submission could not be verified and has been <strong style="color: #ef4444;">rejected</strong>. Please review the details below and resubmit.
            </p>

            {{-- Rejection Reason --}}
            @if($reason)
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #991b1b; font-size: 13px; font-weight: 700; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.5px;">Reason for Rejection</h3>
                <p style="color: #7f1d1d; font-size: 14px; line-height: 1.6; margin: 0;">{{ $reason }}</p>
            </div>
            @endif

            {{-- Payment Details Card --}}
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
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Amount Due</td>
                        <td style="padding: 8px 0; color: #ef4444; font-size: 13px; font-weight: 700; text-align: right;">₱{{ number_format((float) $invoice->balance, 2) }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-size: 13px;">Due Date</td>
                        <td style="padding: 8px 0; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            {{-- Next Steps --}}
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h3 style="color: #92400e; font-size: 13px; font-weight: 700; margin: 0 0 12px;">What to do next:</h3>
                <ol style="color: #78350f; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
                    <li>Log into your tenant portal</li>
                    <li>Go to the Billing section</li>
                    <li>Review your pending invoice</li>
                    <li>Submit a new payment with a clear receipt photo</li>
                </ol>
            </div>

            <p style="color: #94a3b8; font-size: 13px; margin: 0;">
                If you believe this is an error, please contact the management office.
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