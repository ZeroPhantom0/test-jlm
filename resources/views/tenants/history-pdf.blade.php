<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>JLM Cozy Cabin — Payment History</title>
    <style>
        /* Load a font that supports the Peso sign */
        @import url('https://fonts.googleapis.com/css2?family=DejaVu+Sans:wght@400;700&display=swap');
        
        /* Set page margins to zero to remove default browser headers/footers */
        @page { 
            margin: 0; 
            size: A4 portrait;
        }
        
        body { 
            font-family: 'DejaVu Sans', sans-serif; /* Use a Unicode-compatible font */
            color: #333; 
            font-size: 12px; 
            padding: 40px; /* Re-add padding so it doesn't touch the edges */
        }

        .header { text-align: center; border-bottom: 2px solid #02122b; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #02122b; font-size: 24px; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 14px; }

        .meta { margin-bottom: 20px; font-size: 12px; }
        .meta table { width: 100%; }
        .meta td { padding: 5px; }
        .meta .right { text-align: right; }

        .section-title {
            background-color: #f4f6f9; color: #02122b; padding: 8px 12px;
            font-size: 14px; font-weight: bold;
            border-left: 4px solid #10b981;
            margin-top: 25px; margin-bottom: 10px;
        }

        .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .grid-table th, .grid-table td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; vertical-align: top; }
        .grid-table th { background-color: #02122b; color: #ffffff; font-weight: normal; font-size: 11px; }
        .grid-table tr:nth-child(even) { background-color: #f8fafc; }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .font-bold   { font-weight: bold; }

        .badge { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 10px; }
        .badge-green { background-color: #d1fae5; color: #065f46; }
        .badge-amber { background-color: #fef3c7; color: #92400e; }
        .badge-red   { background-color: #ffe4e6; color: #9f1239; }

        .footer {
            text-align: center; color: #94a3b8; font-size: 10px;
            margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>JLM Cozy Cabin</h1>
        <p>Official Payment History Receipt</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td><strong>Tenant:</strong> {{ $user->personalInformation?->full_name ?? $user->name }}</td>
                <td class="right"><strong>Generated On:</strong> {{ now()->format('M d, Y h:i A') }}</td>
            </tr>
            <tr>
                <td><strong>Email:</strong> {{ $user->email }}</td>
                <td class="right"><strong>Total Records:</strong> {{ $events->count() }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Payment Records</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice / Reference</th>
                <th>Description</th>
                <th class="text-right">Amount Paid</th>
                <th class="text-center">Status</th>
                <th>Payment Method</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPaid = 0;
            @endphp
            @forelse($events as $event)
                @php
                    $isPaid = $event['status'] === 'paid';
                    $badge  = $isPaid ? 'badge-green' : 'badge-red';
                    
                    // Ensure Peso sign is properly formatted if present
                    $amountRaw = $event['amount'] ?? '—';
                    $amountDisplay = str_replace('₱', '₱', $amountRaw);

                    // Strip non-numeric for total
                    if ($isPaid && !empty($event['amount'])) {
                        $totalPaid += (float) preg_replace('/[^\d.]/', '', $event['amount']);
                    }
                @endphp
                <tr>
                    <td>{{ $event['date'] }}</td>
                    <td>{{ $event['title'] }}</td>
                    <td>{{ $event['desc'] }}</td>
                    <td class="text-right font-bold">{{ $amountDisplay }}</td>
                    <td class="text-center">
                        <span class="badge {{ $badge }}">{{ ucfirst($event['status']) }}</span>
                    </td>
                    <td>{{ $event['receipt']['method'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 30px; color: #94a3b8;">
                        No payment history found.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($events->count() > 0)
            <tfoot>
                <tr style="background-color: #02122b; color: white;">
                    <td colspan="3" class="font-bold">TOTAL PAID</td>
                    <td class="text-right font-bold" style="color: #10b981;">
                        ₱{{ number_format($totalPaid, 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Generated by JLM Cozy Cabin Tenant Portal • {{ now()->format('Y') }}
    </div>

</body>
</html>