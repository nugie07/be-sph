<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>[New Password] Password Baru Anda</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:40px 20px;background:#fff;">
        <div style="text-align:left;">
            <img src="{{ $storage_url }}/logo/mina-marret-logo.png" alt="Logo" style="height:40px;">
        </div>
        <h2 style="margin-top:40px;">Hi {{ $data['fullname'] }},</h2>
        <p>Password baru Anda telah dibuat. <br> Gunakan password dibawah untuk login ke sistem.</p>

        <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:20px;margin:20px 0;text-align:center;">
            <h3 style="margin:0 0 10px 0;color:#495057;">Password Baru Anda:</h3>
            <h1 style="text-align:center;font-size:1.5em;margin:10px 0;color:#007bff;font-family:monospace;letter-spacing:2px;">{{ $data['new_password'] }}</h1>
        </div>

        <div style="background:#fff3cd;border:1px solid #ffeaa7;border-radius:8px;padding:15px;margin:20px 0;">
            <p style="margin:0;color:#856404;">
                <strong>⚠️ Penting:</strong>
                <br>• Password ini bersifat sementara
                <br>• Silakan ganti password Anda setelah login
                <br>• Jangan bagikan password ini kepada siapapun
            </p>
        </div>

        <hr>
        <div style="margin-top:20px;color:#888;">
            <strong>Need help?</strong>
            <br>
            If you have any questions, please contact us by email at {{ config('mail.support_email') }}.
        </div>
    </div>
</body>
</html>
