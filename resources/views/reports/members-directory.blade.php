<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Members Directory</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #222; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header .meta { margin-top: 4px; color: #666; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; vertical-align: top; }
        th { background-color: #f1f1f1; text-align: left; font-size: 10px; }
        tr:nth-child(even) td { background-color: #fafafa; }
        .footer { margin-top: 14px; font-size: 9px; color: #888; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $parish_name }}</h1>
        <div>Members Directory</div>
        <div class="meta">Generated: {{ $generated_at }} &middot; {{ $members->count() }} members</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Membership No</th>
                <th>Full Name</th>
                <th>Gender</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Society</th>
                <th>Zone</th>
                <th>Status</th>
                <th>Date Joined</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($members as $member)
                <tr>
                    <td>{{ $member->membership_number }}</td>
                    <td>{{ $member->full_name }}</td>
                    <td>{{ ucfirst($member->gender) }}</td>
                    <td>{{ $member->contactDetail?->primary_phone }}</td>
                    <td>{{ $member->contactDetail?->email }}</td>
                    <td>{{ $member->societies->pluck('name')->join(', ') }}</td>
                    <td>{{ $member->zone?->name }}</td>
                    <td>{{ ucfirst($member->status) }}</td>
                    <td>{{ $member->date_joined?->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} {{ $parish_name }} &mdash; ParishHub
    </div>
</body>
</html>
