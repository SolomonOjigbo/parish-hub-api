
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
</head>
<body style="font-family: Arial; background:#f4f4f4; padding:40px;">

    <div style="max-width:600px;margin:auto;background:white;padding:30px;border-radius:10px;">

        <h2 style="color:#111;">
            Reset Your Password
        </h2>

        <p>Hello {{ $name }},</p>

        <p>
            We received a request to reset your password.
        </p>

        <p>
            Your OTP code is:
        </p>

        <h1 style="letter-spacing:5px;color:#2563eb;">
            {{ $otp }}
        </h1>

        <p>
            Or click the button below to reset your password:
        </p>

        <a href="{{ $resetUrl }}"
           style="
                display:inline-block;
                padding:14px 24px;
                background:#2563eb;
                color:white;
                text-decoration:none;
                border-radius:6px;
                margin-top:20px;
           ">
            Reset Password
        </a>

        <p style="margin-top:30px;color:#777;">
            This link expires in 10 minutes.
        </p>

    </div>

</body>
</html>
```
