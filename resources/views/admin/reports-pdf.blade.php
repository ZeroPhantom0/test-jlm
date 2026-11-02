<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>JLM Cozy Cabin - Reports</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #02122b; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #02122b; font-size: 24px; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 14px; }
        
        .meta { margin-bottom: 20px; font-size: 12px; }
        .meta table { width: 100%; }
        .meta td { padding: 5px; }
        .meta .right { text-align: right; }

        .section-title { background-color: #f4f6f9; color: #02122b; padding: 8px 12px; font-size: 14px; font-weight: bold; border-left: 4px solid #10b981; margin-top: 25px; margin-bottom: 10px; }
        
        .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .grid-table th, .grid-table td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        .grid-table th { background-color: #02122b; color: #ffffff; font-weight: normal; }
        .grid-table tr:nth-child(even) { background-color: #f8fafc; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .badge { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 10px; }
        .badge-green { background-color: #d1fae5; color: #065f46; }
        .badge-blue { background-color: #dbeafe; color: #1e40af; }
        .badge-red { background-color: #ffe4e6; color: #9f1239; }
        .badge-amber { background-color: #fef3c7; color: #92400e; }

        .footer { text-align: center; color: #94a3b8; font-size: 10px; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>JLM Cozy Cabin</h1>
        <p>Analytics & Performance Report</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td><strong>Report Range:</strong> {{ ucfirst($range) }}</td>
                <td class="right"><strong>Generated On:</strong> {{ now()->format('M d, Y h:i A') }}</td>
            </tr>
        </table>
    </div>

    {{-- SUMMARY METRICS --}}
    <div class="section-title">Summary Metrics</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th>Total Revenue</th>
                <th>Occupancy Rate</th>
                <th>Active Tenants</th>
                <th>Open Requests</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">₱{{ number_format($summary['revenue'], 0) }}</td>
                <td class="font-bold">{{ $summary['occupancy_rate'] }}%</td>
                <td class="font-bold">{{ $summary['active_tenants'] }}</td>
                <td class="font-bold">{{ $summary['open_requests'] }}</td>
            </tr>
        </tbody>
    </table>

    {{-- OCCUPANCY --}}
    <div class="section-title">Occupancy Breakdown by Room Type</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th>Room Type</th>
                <th class="text-center">Occupied</th>
                <th class="text-center">Reserved</th>
                <th class="text-center">Vacant</th>
                <th class="text-center">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($occupancyByType as $row)
            <tr>
                <td>{{ $row['type'] }}</td>
                <td class="text-center">{{ $row['occupied'] }}</td>
                <td class="text-center">{{ $row['reserved'] }}</td>
                <td class="text-center">{{ $row['vacant'] }}</td>
                <td class="text-center font-bold">{{ $row['total'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- REVENUE --}}
    <div class="section-title">Revenue Breakdown</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th>Period</th>
                <th class="text-right">Rent</th>
                <th class="text-right">Utilities</th>
                <th class="text-right">Other</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $tRent = 0; $tUtil = 0; $tOther = 0; $tTotal = 0; @endphp
            @foreach($revenueBreakdown as $row)
            @php 
                $tRent += $row['rent']; $tUtil += $row['util']; $tOther += $row['other']; $tTotal += $row['total'];
            @endphp
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="text-right">₱{{ number_format($row['rent'], 0) }}</td>
                <td class="text-right">₱{{ number_format($row['util'], 0) }}</td>
                <td class="text-right">₱{{ number_format($row['other'], 0) }}</td>
                <td class="text-right font-bold">₱{{ number_format($row['total'], 0) }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #02122b; color: white;">
                <td class="font-bold">TOTAL</td>
                <td class="text-right">₱{{ number_format($tRent, 0) }}</td>
                <td class="text-right">₱{{ number_format($tUtil, 0) }}</td>
                <td class="text-right">₱{{ number_format($tOther, 0) }}</td>
                <td class="text-right font-bold" style="color: #10b981;">₱{{ number_format($tTotal, 0) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- SERVICES --}}
    <div class="section-title">Service Requests by Category</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th>Period</th>
                <th class="text-center">Plumbing</th>
                <th class="text-center">Electrical</th>
                <th class="text-center">HVAC</th>
                <th class="text-center">General</th>
            </tr>
        </thead>
        <tbody>
            @foreach($serviceByCategory as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="text-center">{{ $row['plumbing'] }}</td>
                <td class="text-center">{{ $row['electrical'] }}</td>
                <td class="text-center">{{ $row['hvac'] }}</td>
                <td class="text-center">{{ $row['general'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- PAYMENTS --}}
    <div class="section-title">Payment Status Summary</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th>Status</th>
                <th class="text-center">Count</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paymentSummary as $status => $data)
            <tr>
                <td>
                    @if($status === 'Paid') <span class="badge badge-green">Paid</span>
                    @elseif($status === 'Unpaid') <span class="badge badge-amber">Unpaid</span>
                    @elseif($status === 'Overdue') <span class="badge badge-red">Overdue</span>
                    @else <span class="badge badge-blue">{{ $status }}</span>
                    @endif
                </td>
                <td class="text-center">{{ $data['count'] }}</td>
                <td class="text-right font-bold">₱{{ number_format($data['amount'], 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated by JLM Cozy Cabin Admin Portal • {{ now()->format('Y') }}
    </div>

</body>
</html>