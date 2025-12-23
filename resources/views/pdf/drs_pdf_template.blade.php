<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$headerTitle}} - Delivery Request Slip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        /* Perubahan hanya di sini untuk membuat header sejajar */
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 120px;
            height: 120px;
            margin-right: 20px;
        }
        h1 {
            margin: 0;
            font-size: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #000;
        }
    </style>
</head>
<body>
    <p style="text-align: right; font-size:14px; margin:0;">{{$drs->drs_no}}</p>
    <p style="text-align: right; font-size:12px; margin:0 0 10px 0;">{{ $drs->drs_unique }}</p>
    
<table style="width:100%; border-collapse: collapse; border: none;" border="0">
  <tr>
    <td style="border:none; padding: 10px 35px 10px 10px; text-align: center; vertical-align: top; width:160px;">
      <img src="{{ $logoUrl ?? public_path('static/images/logo/logo.png') }}" alt="Logo" class="logo" style="width:140px; height:auto; vertical-align: top; margin-top: 0;">
    </td>
    <td style="border:none; padding: 12px 5px 8px 5px; vertical-align: top;">
      <div style="display:flex; flex-direction:column; justify-content:flex-start;">
        <br><br><span style="font-size:20px; margin:0; font-weight:bold; line-height:1;">{{ $headerTitle }}</span>
      <br>  <span style="font-size:14px; margin:4px 0 0 2px; line-height:1;">Delivery Request Slip</span>
      </div>
    </td>
  </tr>
</table>
    <table>
        <tr>
            <th>Nama Klien</th>
            <td>{{$drs->customer_name}}</td>
        </tr>
        <tr>
            <th>No PO</th>
            <td>{{$drs->po_number}}</td>
        </tr>
        <tr>
            <th>Tanggal PO</th>
            <td>{{$drs->po_date}}</td>
        </tr>
        <tr>
            <th>Source</th>
            <td>{{ $drs->source}}</td>
        </tr>
        <tr>
            <th>Volume</th>
            <td>{{$drs->volume}} Liter</td>
        </tr>
        <tr>
            <th>Truck Capacity</th>
            <td>{{$drs->truck_capacity}} KL</td>
        </tr>
        <tr>
            <th>Request Date by</th>
            <td>{{$drs->request_date}}</td>
        </tr>
        <tr>
            <th>Nama Transportir</th>
            <td>{{$drs->transporter_name}}</td>
        </tr>
        <tr>
            <th>Site Location</th>
            <td>{{$drs->site_location}}</td>
        </tr>
        <tr>
            <th>Delivery Note</th>
            <td>{{$drs->dn_no}}</td>
        </tr>
        <tr>
            <th>PIC Site</th>
            <td>{{$drs->pic_site}}</td>
        </tr>
        <tr>
            <th>No PIC Site</th>
            <td>{{ $drs->pic_site_telp }}</td>
        </tr>
        <tr>
            <th>Requested by</th>
            <td>{{$drs->requested_by}}</td>
        </tr>
        <tr>
            <th>Additional Note</th>
            <td>{{$drs->additional_note}}</td>
        </tr>
    </table>

    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="signature-box">
                    <p>Acknowledged by</p>
                    <div class="signature-line"></div>
                    <p>(......................)</p>
                </div>
            </td>
                <td>
                    <div class="signature-box">
                        <p>Requested by :</p>
                        <div class="signature-line"></div>
                        <p>{{$drs->requested_by}}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
