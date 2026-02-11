<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>[Admin Notification] Password User Telah Direset</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:40px 20px;background:#fff;">
        <div style="text-align:left;">
            <img src="{{ $storage_url }}/logo/mina-marret-logo.png" alt="Logo" style="height:40px;">
        </div>
        <h2 style="margin-top:40px;">Hi Admin Support,</h2>
        <p>Password user telah direset oleh administrator. <br> Berikut adalah detail reset password yang telah dilakukan.</p>

        <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:20px;margin:20px 0;">
            <h3 style="margin:0 0 10px 0;color:#495057;">Detail Reset Password:</h3>
            <div style="text-align:left;margin:10px 0;">
                <p style="margin:5px 0;"><strong>Nama User:</strong> {{ $data['fullname'] }}</p>
                <p style="margin:5px 0;"><strong>Email:</strong> {{ $data['email'] }}</p>
                <p style="margin:5px 0;"><strong>Password Baru:</strong> {{ $data['password'] }}</p>
                <p style="margin:5px 0;"><strong>Direset Oleh:</strong> {{ $data['reset_by'] }}</p>
                <p style="margin:5px 0;"><strong>Waktu Reset:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
            </div>
        </div>

        <div style="background:#e2e3e5;border:1px solid #d6d8db;border-radius:8px;padding:15px;margin:20px 0;">
            <p style="margin:0;color:#383d41;">
                <strong>📋 Informasi:</strong>
                <br>• Email telah dikirim ke user dengan password baru
                <br>• User diminta untuk mengganti password setelah login
                <br>• Jika ada masalah, silakan hubungi administrator
            </p>
        </div>

        <div style="background:#d1ecf1;border:1px solid #bee5eb;border-radius:8px;padding:15px;margin:20px 0;">
            <p style="margin:0;color:#0c5460;">
                <strong>🔍 Monitoring:</strong>
                <br>• Pastikan user dapat login dengan password baru
                <br>• Monitor aktivitas login user setelah reset
                <br>• Jika user melaporkan masalah, bantu dengan segera
            </p>
        </div>

        <hr>
        <div style="margin-top:20px;color:#888;">
            <strong>Admin Support Team</strong>
            <br>
            Aplikasi SPH - Mina Marret
        </div>
    </div>
</body>
</html>
