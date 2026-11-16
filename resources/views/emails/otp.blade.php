<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
</head>
<body style="font-family: Arial; background:#f5f5f5; padding:20px;">

    <div style="max-width:600px; margin:auto; background:#fff; padding:30px; border-radius:10px;">

        <h2 style="color:#0f172a;">
            Verify Your Email
        </h2>

        <p>Hello,</p>

        <p>
            Use the OTP below to verify your account:
        </p>

        <div style="
            font-size:32px;
            font-weight:bold;
            letter-spacing:5px;
            background:#f1f5f9;
            padding:20px;
            text-align:center;
            border-radius:8px;
            margin:20px 0;
        ">
            {{ $otp }}
        </div>

        <p>
            This OTP expires in 10 minutes.
        </p>

        <p>
            Thank you,<br>
            {{ config('app.name') }}
        </p>

    </div>

</body>
</html>