<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Giving Statement {{ $year }} — {{ $member->full_name }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1a1a2e; margin: 30px; font-size: 12px; }
        .header { text-align: center; border-bottom: 3px solid #E8541A; padding-bottom: 12px; }
        .header h1 { margin: 0; font-size: 19px; }
        .header p { margin: 3px 0 0; color: #666; font-size: 11px; }
        .member { margin-top: 16px; }
        .member strong { font-size: 14px; }
        h2 { font-size: 13px; color: #E8541A; border-bottom: 1px solid #eee; padding-bottom: 4px; margin: 22px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; color: #888; padding: 6px 8px; border-bottom: 1px solid #ddd; }
        td { padding: 6px 8px; border-bottom: 1px solid #f2f2f2; }
        td.num, th.num { text-align: right; }
        .subtotal td { font-weight: bold; background: #fafafa; }
        .grand { margin-top: 26px; background: #FFF4EF; border: 1px solid #E8541A; padding: 12px 16px; text-align: right; font-size: 15px; }
        .grand strong { color: #E8541A; font-size: 18px; }
        .footer { margin-top: 30px; text-align: center; color: #888; font-size: 10px; }
        .empty { color: #999; font-style: italic; padding: 6px 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $parish_name }}</h1>
        <p>{{ $diocese }}</p>
        <p>Annual Giving Statement — {{ $year }}</p>
    </div>

    <p class="member">
        <strong>{{ $member->full_name }}</strong> &nbsp;({{ $member->membership_number }})<br>
        @if($member->contactDetail?->address_line1){{ $member->contactDetail->address_line1 }}@if($member->contactDetail?->lga), {{ $member->contactDetail->lga }}@endif @endif
    </p>

    <h2>Offerings</h2>
    <table>
        <thead><tr><th>Date</th><th>Envelope</th><th>Method</th><th class="num">Amount (&#8358;)</th></tr></thead>
        <tbody>
        @forelse($offerings as $o)
            <tr><td>{{ $o->collection_date->format('d M Y') }}</td><td>{{ $o->envelope_number ?? '—' }}</td><td>{{ ucwords(str_replace('_',' ',$o->payment_method)) }}</td><td class="num">{{ number_format((float) $o->amount, 2) }}</td></tr>
        @empty
            <tr><td colspan="4" class="empty">No offerings recorded.</td></tr>
        @endforelse
        @if($offerings->isNotEmpty())
            <tr class="subtotal"><td colspan="3">Subtotal</td><td class="num">{{ number_format((float) $offerings->sum('amount'), 2) }}</td></tr>
        @endif
        </tbody>
    </table>

    <h2>Tithes</h2>
    <table>
        <thead><tr><th>Period</th><th>Paid on</th><th>Method</th><th class="num">Amount (&#8358;)</th></tr></thead>
        <tbody>
        @forelse($tithes as $t)
            <tr><td>{{ \Carbon\Carbon::create($t->period_year, $t->period_month, 1)->format('F Y') }}</td><td>{{ $t->payment_date->format('d M Y') }}</td><td>{{ ucwords(str_replace('_',' ',$t->payment_method)) }}</td><td class="num">{{ number_format((float) $t->amount, 2) }}</td></tr>
        @empty
            <tr><td colspan="4" class="empty">No tithes recorded.</td></tr>
        @endforelse
        @if($tithes->isNotEmpty())
            <tr class="subtotal"><td colspan="3">Subtotal</td><td class="num">{{ number_format((float) $tithes->sum('amount'), 2) }}</td></tr>
        @endif
        </tbody>
    </table>

    <h2>Donations</h2>
    <table>
        <thead><tr><th>Date</th><th>Purpose</th><th>Method</th><th class="num">Amount (&#8358;)</th></tr></thead>
        <tbody>
        @forelse($donations as $d)
            <tr><td>{{ $d->donation_date->format('d M Y') }}</td><td>{{ $d->purpose }}</td><td>{{ ucwords(str_replace('_',' ',$d->payment_method)) }}</td><td class="num">{{ number_format((float) $d->amount, 2) }}</td></tr>
        @empty
            <tr><td colspan="4" class="empty">No donations recorded.</td></tr>
        @endforelse
        @if($donations->isNotEmpty())
            <tr class="subtotal"><td colspan="3">Subtotal</td><td class="num">{{ number_format((float) $donations->sum('amount'), 2) }}</td></tr>
        @endif
        </tbody>
    </table>

    <h2>Pledge Payments</h2>
    <table>
        <thead><tr><th>Date</th><th>Pledge</th><th>Method</th><th class="num">Amount (&#8358;)</th></tr></thead>
        <tbody>
        @forelse($pledge_payments as $pp)
            <tr><td>{{ $pp->payment_date->format('d M Y') }}</td><td>{{ $pp->pledge?->purpose ?? '—' }}</td><td>{{ ucwords(str_replace('_',' ',$pp->payment_method)) }}</td><td class="num">{{ number_format((float) $pp->amount, 2) }}</td></tr>
        @empty
            <tr><td colspan="4" class="empty">No pledge payments recorded.</td></tr>
        @endforelse
        @if($pledge_payments->isNotEmpty())
            <tr class="subtotal"><td colspan="3">Subtotal</td><td class="num">{{ number_format((float) $pledge_payments->sum('amount'), 2) }}</td></tr>
        @endif
        </tbody>
    </table>

    <div class="grand">Total giving in {{ $year }}: &nbsp;<strong>&#8358;{{ number_format((float) $total, 2) }}</strong></div>

    <p class="footer">Generated by ParishHub on {{ now()->format('j M Y, g:i a') }}. Thank you for your faithful stewardship — God bless you.</p>
</body>
</html>
