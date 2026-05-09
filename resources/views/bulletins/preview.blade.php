<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bulletin->title }} - {{ $parish_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; margin: 0; }
        .header p { color: #7f8c8d; margin: 5px 0; }
        .content { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $parish_name }}</h1>
        <p>Sunday Bulletin</p>
        <p>{{ $bulletin->sunday_date->format('F j, Y') }}</p>
    </div>

    <div class="content">
        {!! $bulletin->content !!}
    </div>

    <p style="margin-top: 30px; text-align: center; color: #95a5a6;">
        Generated on: {{ now()->format('F j, Y, g:i a') }}
    </p>
</body>
</html>
