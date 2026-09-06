<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ ucwords(str_replace('_', ' ', $record->type)) }} Certificate</title>
    <style>
        @page { margin: 40px; }
        body { font-family: DejaVu Serif, Georgia, serif; color: #1a1a2e; margin: 0; }
        .border-frame { border: 4px double #8B0000; padding: 40px 50px; min-height: 640px; }
        .header { text-align: center; }
        .header .cross { font-size: 30px; color: #8B0000; }
        .header h1 { margin: 6px 0 0; font-size: 24px; letter-spacing: 1px; }
        .header .diocese { margin: 4px 0 0; font-size: 13px; color: #555; }
        .header .address { margin: 2px 0 0; font-size: 11px; color: #777; }
        .title { text-align: center; margin: 34px 0 6px; font-size: 28px; color: #8B0000; }
        .subtitle { text-align: center; font-size: 12px; color: #666; letter-spacing: 2px; text-transform: uppercase; }
        .body-text { margin: 36px 40px 0; font-size: 15px; line-height: 2.1; text-align: center; }
        .body-text .name { font-size: 20px; font-weight: bold; border-bottom: 1px dotted #999; padding: 0 12px; }
        .detail { border-bottom: 1px dotted #999; padding: 0 10px; font-weight: bold; }
        .register { margin: 40px 40px 0; font-size: 11px; color: #555; text-align: center; }
        .signatures { margin: 60px 40px 0; width: calc(100% - 80px); }
        .signatures td { width: 50%; text-align: center; font-size: 12px; color: #444; padding-top: 8px; }
        .sig-line { border-top: 1px solid #333; margin: 0 30px; padding-top: 6px; }
    </style>
</head>
<body>
<div class="border-frame">
    <div class="header">
        <div class="cross">&#8224;</div>
        <h1>{{ $parish_name }}</h1>
        <p class="diocese">{{ $diocese }}</p>
        <p class="address">{{ $address }}</p>
    </div>

    <h2 class="title">
        @switch($record->type)
            @case('baptism') Certificate of Baptism @break
            @case('first_communion') Certificate of First Holy Communion @break
            @case('confirmation') Certificate of Confirmation @break
            @case('marriage') Certificate of Holy Matrimony @break
            @case('holy_orders') Certificate of Holy Orders @break
            @default Sacramental Certificate
        @endswitch
    </h2>
    <div class="subtitle">Ex Libris Ecclesiae</div>

    <div class="body-text">
        This is to certify that<br>
        <span class="name">{{ $member->full_name }}</span><br>
        @if($record->type === 'marriage' && $record->spouse_name)
            and <span class="detail">{{ $record->spouse_name }}</span><br>
            were joined in Holy Matrimony
        @else
            received the Sacrament of
            {{ ucwords(str_replace('_', ' ', $record->type)) }}
        @endif
        @if($record->date)
            on <span class="detail">{{ $record->date->format('jS F, Y') }}</span>
        @endif
        <br>
        at <span class="detail">{{ $record->church ?? $parish_name }}</span>
        @if($record->minister)
            <br>by <span class="detail">{{ $record->minister }}</span>
        @endif
    </div>

    <p class="register">
        Extracted from the parish register &nbsp;•&nbsp; Member No. {{ $member->membership_number }}
        &nbsp;•&nbsp; Issued {{ now()->format('jS F, Y') }}
    </p>

    <table class="signatures">
        <tr>
            <td><div class="sig-line">Parish Priest</div></td>
            <td><div class="sig-line">Parish Seal</div></td>
        </tr>
    </table>
</div>
</body>
</html>
