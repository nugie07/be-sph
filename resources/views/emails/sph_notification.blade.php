<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPH Notification</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:40px 20px;background:#fff;">
        <div style="text-align:left;">
            <img src="{{ $storage_url }}/logo/mina-marret-logo.png" alt="Logo" style="height:40px;">
        </div>
        <h2 style="margin-top:40px;">Hi {{ $data['fullname'] }},</h2>
        <p>Berikut kami kirim Surat Penawaran Harga.</p>
        <table style="width:100%;margin-top:40px;margin-bottom:30px;font-size:1.1em;">
            <tr>
                <td>Nama Customer</td>
                <td style="font-weight:bold;">{{ $data['company_name'] }}</td>
            </tr>
            <tr>
                <td>SPH Code:</td>
                <td style="font-weight:bold;">{{ $data['sph_kode'] }}</td>
            </tr>
            <tr>
                <td>Product:</td>
                <td style="font-weight:bold;">{{ $data['product'] }}</td>
            </tr>
            <tr>
                <td>Total Harga:</td>
                <td style="font-weight:bold;">{{ $data['total'] }}</td>
            </tr>
        </table>
        <div style="text-align:center;margin-bottom:32px;">
            <a href="{{ $data['file_url'] }}" style="display:inline-block;padding:18px 36px;background:#5856e8;color:#fff;text-decoration:none;border-radius:6px;font-size:1.1em;">
                Download Surat Penawaran Harga
            </a>
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