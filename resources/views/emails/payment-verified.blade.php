<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verified</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 32px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 600;">JLM Cozy Cabin</h1>
            <p style="color: #ecfdf5; margin: 8px 0 0; font-size: 14px;">Payment Successfully Verified</p>
        </div>
        <div style="padding: 32px;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 16px;">Hi {{ $invoice->tenant_name }},</p>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                We are pleased to inform you that your payment has been verified and recorded successfully.
            </p>
            
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 13px;">Invoice No:</span>
                    <strong style="color: #0f172a; font-size: 13px;">{{ $invoice->transaction_no }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 13px;">Description:</span>
                    <strong style="color: #0f172a; font-size: 13px;">{{ $invoice->description }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 13px;">Amount Paid:</span>
                    <strong style="color: #10b981; font-size: 13px;">₱{{ number_format($invoice->amount_paid, 2) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 13px;">Payment Method:</span>
                    <strong style="color: #0f172a; font-size: 13px;">{{ $invoice->payment_method }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b; font-size: 13px;">Date Paid:</span>
                    <strong style="color: #0f172a; font-size: 13px;">{{ now()->format('M d, Y') }}</strong>
                </div>
            </div>

            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0;">
                You can view and download your official receipt by logging into your tenant portal.
            </p>
        </div>
    </div>
</body>
</html>