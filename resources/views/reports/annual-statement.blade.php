<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annual Financial Statement - {{ $parish_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header img { max-width: 100px; margin-bottom: 10px; }
        .header h1 { color: #2c3e50; margin: 0; }
        .header p { color: #7f8c8d; margin: 5px 0; }
        .section { margin-bottom: 30px; }
        .section h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
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
        <p>Annual Financial Statement for Diocesan Submission</p>
        <p>Year: {{ $data['year'] }}</p>
    </div>

    <div class="section">
        <h2>Income by Category</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount (₦)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Offerings</td>
                    <td>{{ number_format($data['income_by_category']['offerings'], 2) }}</td>
                </tr>
                <tr>
                    <td>Tithes</td>
                    <td>{{ number_format($data['income_by_category']['tithes'], 2) }}</td>
                </tr>
                <tr>
                    <td>Donations</td>
                    <td>{{ number_format($data['income_by_category']['donations'], 2) }}</td>
                </tr>
                <tr>
                    <td>Pledge Payments</td>
                    <td>{{ number_format($data['income_by_category']['pledge_payments'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Income</td>
                    <td class="grand-total">{{ number_format($data['income_by_category']['total_income'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Weekly Offering Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Week</th>
                    <th>Total (₦)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['weekly_offering_breakdown'] as $week)
                <tr>
                    <td>{{ $week['week'] }}</td>
                    <td>{{ number_format($week['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Top Donors</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Total Given (₦)</th>
                    <th>Donation Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['top_donors'] as $index => $donor)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $donor['name'] }}</td>
                    <td>{{ number_format($donor['total_given'], 2) }}</td>
                    <td>{{ $donor['donation_count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p style="margin-top: 30px; text-align: center; color: #95a5a6;">
        Generated on: {{ now()->format('F j, Y, g:i a') }}
    </p>
</body>
</html>
