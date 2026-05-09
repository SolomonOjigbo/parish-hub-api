<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Summary - {{ $parish_name }}</title>
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
        .total-row { font-weight: bold; background-color: #ecf0f1 !important; }
        .grand-total { font-size: 1.2em; color: #27ae60; }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists($logo_path))
        <img src="{{ $logo_path }}" alt="Parish Logo">
        @endif
        <h1>{{ $parish_name }}</h1>
        <p>Financial Summary Report</p>
        <p>Period: {{ $data['from'] }} to {{ $data['to'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Offerings</th>
                <th>Tithes</th>
                <th>Donations</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['monthly_data'] as $month)
            <tr>
                <td>{{ $month['month'] }}</td>
                <td>{{ number_format($month['offerings'], 2) }}</td>
                <td>{{ number_format($month['tithes'], 2) }}</td>
                <td>{{ number_format($month['donations'], 2) }}</td>
                <td>{{ number_format($month['total'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Grand Total</td>
                <td>{{ number_format(collect($data['monthly_data'])->sum('offerings'), 2) }}</td>
                <td>{{ number_format(collect($data['monthly_data'])->sum('tithes'), 2) }}</td>
                <td>{{ number_format(collect($data['monthly_data'])->sum('donations'), 2) }}</td>
                <td class="grand-total">{{ number_format($data['grand_total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 30px; text-align: center; color: #95a5a6;">
        Generated on: {{ now()->format('F j, Y, g:i a') }}
    </p>
</body>
</html>
