<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
</head>
<body>
    <h1>Password Reset Request</h1>
    <p>You have requested to reset your password for your account. Use the OTP below to proceed with resetting your password:</p>
    <p><strong>OTP: {{ $otp }}</strong></p>
    <p>This OTP is valid for 10 minutes.</p>
    <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
</body>
</html>
