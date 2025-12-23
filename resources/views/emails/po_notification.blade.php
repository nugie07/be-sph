<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PO Notification</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:40px 20px;background:#fff;">
        <div style="text-align:left;">
            <img src="https://is3.cloudhost.id/bensinkustorage/logo/mina-marret-logo.png" alt="Logo" style="height:40px;">
        </div>
        <h2 style="margin-top:40px;">Hi {{ $data['fullname'] }},</h2>
        <p>Berikut kami infokan bahwa ada Purchase Order dari customer .</p>
        <table style="width:100%;margin-top:40px;margin-bottom:30px;font-size:1.1em;">
            <tr>
                <td>Nama Customer</td>
                <td style="font-weight:bold;">{{ $data['customer'] }}</td>
            </tr>
            <tr>
                <td>SPH Code:</td>
                <td style="font-weight:bold;">{{ $data['sph'] }}</td>
            </tr>
            <tr>
                <td>Nomer PO:</td>
                <td style="font-weight:bold;">{{ $data['no_po'] }}</td>
            </tr>
            <tr>
                <td>Nilai PO:</td>
                <td style="font-weight:bold;">Rp {{ number_format($data['total'], 0, ',', '.') }}<br>
                    <span><b><i>{{ $data['terbilang'] }}</i></b></span></td>
            </tr>
        </table>
        <div style="text-align:center;margin-bottom:32px;">
            <a href="{{ $data['file'] }}" style="display:inline-block;padding:18px 36px;background:#5856e8;color:#fff;text-decoration:none;border-radius:6px;font-size:1.1em;">
                Download PO
            </a>
        </div>
        <hr>
        <div style="margin-top:20px;color:#888;">
            <strong>Need help?</strong>
            <br>
            If you have any questions, please contact us by email at {{ env('MAIL_SUPPORT_EMAIL') ?? 'support@email.com' }}.
        </div>
    </div>
</body>
</html>