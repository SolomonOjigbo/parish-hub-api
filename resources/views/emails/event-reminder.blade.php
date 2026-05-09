<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222;">
    <h2>{{ $event->title }}</h2>
    <p>Dear {{ $member->full_name }},</p>
    <p>This is a reminder that you are registered for the following event:</p>

    <table cellpadding="6" style="border-collapse: collapse;">
        <tr><td><strong>Date</strong></td><td>{{ $event->start_datetime->format('l, j F Y g:i A') }}</td></tr>
        @if ($event->location)
        <tr><td><strong>Location</strong></td><td>{{ $event->location }}</td></tr>
        @endif
        @if ($event->description)
        <tr><td valign="top"><strong>Details</strong></td><td>{!! nl2br(e($event->description)) !!}</td></tr>
        @endif
    </table>

    <p>We look forward to seeing you.</p>
    <p>&mdash; St. Ferdinand Catholic Church, Lagos</p>
</body>
</html>
