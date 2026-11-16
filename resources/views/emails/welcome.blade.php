<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
</head>
<body style="font-family: Arial; background:#f5f5f5; padding:20px;">

<div style="max-width:600px; margin:auto; background:#fff; padding:30px; border-radius:10px;">

    <h2>
        Welcome to {{ config('app.name') }}
    </h2>

    <p>
        Hello {{ $name }},
    </p>

    <p>
        Your account has been created successfully.
    </p>

    <p>
        Thank you for joining us.
    </p>

</div>

</body>
</html>