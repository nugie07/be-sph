<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>[OTP Verification] Permintaan Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:40px 20px;background:#fff;">
        <div style="text-align:left;">
            <img src="https://is3.cloudhost.id/bensinkustorage/logo/mina-marret-logo.png" alt="Logo" style="height:40px;">
        </div>
        <h2 style="margin-top:40px;">Hi {{ $data['fullname'] }},</h2>
        <p>Berikut kami infokan bahwa ada Permintaan Reset Password, <br> Gunakan OTP dibawah untuk validasi .</p>

        <h1 style="text-align:center;font-size:2em;margin-top:40px;">{{ $data['otp'] }}</h1>
        <hr>
        <div style="margin-top:20px;color:#888;">
            <strong>Need help?</strong>
            <br>
            If you have any questions, please contact us by email at {{ env('MAIL_SUPPORT_EMAIL') ?? 'support@email.com' }}.
        </div>
    </div>
</body>
</html>
