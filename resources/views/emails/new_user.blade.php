<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>[Welcome] Selamat Datang di Aplikasi SPH</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:40px 20px;background:#fff;">
        <div style="text-align:left;">
            <img src="https://is3.cloudhost.id/bensinkustorage/logo/mina-marret-logo.png" alt="Logo" style="height:40px;">
        </div>
        <h2 style="margin-top:40px;">Hi {{ $data['fullname'] }},</h2>
        <p>Selamat datang di Aplikasi SPH! <br> Akun Anda telah berhasil dibuat dan Anda telah diundang untuk bergabung dengan sistem.</p>

        <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:20px;margin:20px 0;">
            <h3 style="margin:0 0 10px 0;color:#495057;">Informasi Login Anda:</h3>
            <div style="text-align:left;margin:10px 0;">
                <p style="margin:5px 0;"><strong>Email:</strong> {{ $data['email'] }}</p>
                <p style="margin:5px 0;"><strong>Password:</strong> {{ $data['password'] }}</p>
            </div>
        </div>

        <div style="text-align:center;margin:30px 0;">
            <a href="{{ env('FE_URL', 'https://app.example.com') }}" style="background:#007bff;color:#fff;padding:12px 30px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">
                🚀 Login ke Aplikasi
            </a>
        </div>

        <div style="background:#fff3cd;border:1px solid #ffeaa7;border-radius:8px;padding:15px;margin:20px 0;">
            <p style="margin:0;color:#856404;">
                <strong>⚠️ Penting:</strong>
                <br>• Password ini bersifat sementara
                <br>• Silakan ganti password Anda setelah login pertama
                <br>• Jangan bagikan kredensial login kepada siapapun
                <br>• Pastikan untuk logout setelah selesai menggunakan aplikasi
            </p>
        </div>

        <div style="background:#d1ecf1;border:1px solid #bee5eb;border-radius:8px;padding:15px;margin:20px 0;">
            <p style="margin:0;color:#0c5460;">
                <strong>💡 Tips Keamanan:</strong>
                <br>• Gunakan password yang kuat dan unik
                <br>• Laporkan aktivitas mencurigakan kepada admin
            </p>
        </div>

        <hr>
        <div style="margin-top:20px;color:#888;">
            <strong>Need help?</strong>
            <br>
            If you have any questions or need assistance, please contact us by email at {{ env('MAIL_SUPPORT_EMAIL') ?? 'support@email.com' }}.
        </div>
    </div>
</body>
</html>
