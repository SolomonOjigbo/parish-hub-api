<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pledge Report - {{ $parish_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header img { max-width: 100px; margin-bottom: 10px; }
        .header h1 { color: #2c3e50; margin: 0; }
        .header p { color: #7f8c8d; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #3498db; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-completed { background-color: #d5f5e3 !important; }
        .status-overdue { background-color: #fadbd8 !important; }
        .status-active { background-color: #fef9e7 !important; }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists($logo_path))
        <img src="{{ $logo_path }}" alt="Parish Logo">
        @endif
        <h1>{{ $parish_name }}</h1>
        <p>Pledge Fulfilment Report</p>
        <p>Period: {{ $data['from'] }} to {{ $data['to'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Purpose</th>
                <th>Total Amount</th>
                <th>Amount Paid</th>
                <th>Balance</th>
                <th>Completion %</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['pledges'] as $pledge)
            <tr class="status-{{ $pledge['status'] }}">
                <td>{{ $pledge['id'] }}</td>
                <td>{{ $pledge['member_name'] }}</td>
                <td>{{ $pledge['purpose'] }}</td>
                <td>{{ number_format($pledge['total_amount'], 2) }}</td>
                <td>{{ number_format($pledge['amount_paid'], 2) }}</td>
                <td>{{ number_format($pledge['balance'], 2) }}</td>
                <td>{{ number_format($pledge['completion_percentage'], 2) }}%</td>
                <td>{{ ucfirst($pledge['status']) }}</td>
                <td>{{ $pledge['start_date'] ?? 'N/A' }}</td>
                <td>{{ $pledge['end_date'] ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 30px; text-align: center; color: #95a5a6;">
        Generated on: {{ now()->format('F j, Y, g:i a') }}
    </p>
</body>
</html>
