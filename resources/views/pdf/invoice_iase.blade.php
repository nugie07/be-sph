<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
            margin: 0;
            padding: 40px;
            font-size: 14px;
            line-height: 1;
        }

        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .header h4 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 4px;
            margin-bottom: 10px;
        }

        .header small {
            font-size: 10px;
            color: #666;
            line-height: 1.5;
            display: block;
        }

        .content {
            font-size: 12px;
        }

        .content table {
            margin-bottom: 14px;
        }

        .table {
            border-collapse: collapse;
        }

        .table th {
            padding: 8px;
            text-align: center;
        }

        .table td {
            padding: 5px;
        }

        .table .amount-column {
            border: 1px solid black;
        }

        .table .label {
            text-align: left;
        }

        .table .info {
            padding-left: 16px;
        }

        .tableoat {
            border-collapse: collapse;
        }

        .tableoat th {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
            background-color: #f8f8f8;
        }

        .tableoat td {
            border: 1px solid black;
            padding: 5px;
        }

        .tableoat td:nth-child(2),
        .tableoat td:nth-child(3) {
            text-align: center;
        }

        td[style*="padding-right"],
        td[style*="padding-left"] {
            vertical-align: top;
        }

        .remarks {
            margin: 16px 0;
        }

        ol {
            padding-left: 50px;
            margin: 16px 0;
        }

        ol li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .footer {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 32px;
        }

        .footer img {
            height: 50px;
            object-fit: contain;
        }

        a {
            color: #0066cc;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .mb-16 {
            margin-bottom: 16px;
        }

        .mb-32 {
            margin-bottom: 32px;
        }

        p {
            margin-bottom: 8px;
        }

        /* Horizontal Rule */
        hr.thick-line {
            margin: 0;
            /* hapus margin default */
            border: none;
            border-top: 5px solid #2b2e34;
            /* warna dan ketebalan garis */
        }

        table.invoice-info {
            margin-top: 0px;
            /* rapat ke garis atas */
        }

        .invoice-info td {
            padding-top: 4px;
            padding-bottom: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td width="10%">
                        <div style="text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 16px;">
                            {!! $invoice->type == 1 ? 'INVOICE' : 'PROFORMA <br> INVOICE' !!}
                        </div>

                    </td>
                    <td width="80%">
                        <h4>PT INDO ANUGERAH SUKSES ENERGI</h4>
                        <p>AGEN BBM INDUSTRI</p>
                        <small>
                            World Capital Tower LT. 05 Unit 01, Jl. DR. Ide Anak Agung Gde Lot D. Lingkar Mega <br>
                            Kuningan Kota Administrasi Jakarta Selatan DKI Jakarta 12950<br />
                            Gagah Putera Satria Building, Jl. KP. Tendean No. 158 Banjarmasin, Kalimantan Selatan
                            70231<br />
                            Telp: +62-811-888-2221
                        </small>
                    </td>
                    <td width="10%">
                        <img src="{{ $storage_url }}/logo/logo_iase.png"
                            alt="Company Logo" class="w-5 h-5 sm:w-5 sm:h-5 object-contain rounded-lg">
                    </td>
                </tr>
            </table><br>
            <hr class="thick-line">

            <table class="invoice-info"
                style="width: 100%; table-layout: fixed; border-collapse: collapse; text-align: left; font-size: 11px;">
                <tr>
                    <td style="width: 20%; padding: 4px;"><b>INVOICE NO:</b><br> {{ $invoice->invoice_no }}</td>
                    <td style="width: 25%; padding: 4px;"><b>INVOICE DATE:</b><br> {{ $invoice->invoice_date }}</td>
                    <td style="width: 30%; padding: 4px;"><b>TERMS:</b><br> {{ $invoice->terms }}</td>
                    <td style="width: 25%; padding: 4px;"><b>PO NO:</b><br>{{ $invoice->po_no }}</td>
                </tr>
            </table>
        </div>

        <div class="content">

            <!-- BAGIAN INI DIUBAH MENJADI 3 KOLOM: BILL TO, SHIP TO, dan NOTES -->
            <table style="width: 100%; font-size: 11px; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <!-- Kolom 1: Bill To -->
                    <td style="width: 35%; vertical-align: top; text-align: left; padding-right: 10px;">
                        <b>BILL TO :</b><br>
                        {{ $invoice->bill_to }}<br>
                           Address :<span class="text-xs">{{ $invoice->bill_to_address ?? 'N/A' }}</span>
                    </td>

                    <!-- Kolom 2: Ship To -->
                    <td style="width: 35%; vertical-align: top; text-align: left; padding-right: 10px;">
                        <b>SHIP TO :</b><br>
                        {{ $invoice->ship_to }}<br>
                        Address :<span class="text-xs">{{ $invoice->ship_to_address ?? 'N/A' }}</span>
                    </td>

                    <!-- Kolom 3: Notes (Data pindahan dari box) -->
                    <td style="width: 30%; vertical-align: top; text-align: left;">
                        <b>NOTES :</b><br>
                        <table style="width: 100%; font-size: 11px; border-collapse: collapse; margin-top: 2px;">
                            <tr>
                                <td style="padding: 1px 0; width: 70px;">F.O.B Point</td>
                                <td style="padding: 1px 0;">: {{ $invoice->fob ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 1px 0;">Sent Date</td>
                                <td style="padding: 1px 0;">: {{ $invoice->sent_date ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 1px 0;">Sent Via</td>
                                <td style="padding: 1px 0;">: {{ $invoice->sent_via ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!-- BATAS PERUBAHAN -->

        </div>

        <!-- Table Item -->
        <table class="table" style="width: 100%; font-size: 11px; border-collapse: collapse; table-layout: fixed;">
                <thead>
                    <tr style="background-color: #f3f3f3; text-transform: uppercase;">
                        <th class="amount-column" colspan="2" style="width: 15%;">QUANTITY</th>
                        
                        <th class="amount-column" style="width: 40%;">DESCRIPTION</th>
                        <th class="amount-column" style="width: 15%;">UNIT PRICE</th>
                        <th class="amount-column" style="width: 10%;">DISKON</th>
                        <th class="amount-column" style="width: 20%;">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
            @foreach($details as $index => $detail)
            <tr>
                <td class="amount-column" style="text-align: center;">
                    {{ number_format($detail->qty ?? 0, 0, ',', '.') }}
                </td>

                <td class="amount-column" style="text-align: center;">
                    Liter
                </td>

                <td class="amount-column" style="text-align: center;">{{ $detail->nama_item ?? 'N/A' }}</td>
                <td class="amount-column" style="text-align: right;"><span style="float: left;">Rp</span> {{ number_format($detail->harga ?? 0, 0, ',', '.') }}</td>
                <td class="amount-column" style="text-align: right;"><span style="float: left;">Rp</span> {{ number_format($detail->diskon ?? 0, 0, ',', '.') }}</td>
                <td class="amount-column" style="text-align: right;"><span style="float: left;">Rp</span> {{ number_format($detail->total ?? 0, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

        <!-- REKENING PEMBAYARAN + TABEL TOTAL -->
        <table style="width: 100%; font-size: 11px; margin-bottom: 20px;">
            <tr>
                <!-- Kolom REKENING -->
                <td style="vertical-align: top; width: 60%; padding-top: 50px;">
                    <p style="font-weight: bold;">REKENING PEMBAYARAN:</p>
                    <p>BCA CABANG WTC SUDIRMAN,JAKARTA PUSAT</p>
                    <p> AN. PT INDO ANUGERAH SUKSES ENERGI</p>
                    <p>ACCT NO. 5455-345798</p>
                </td>

                <!-- Kolom TOTAL -->
                <td style="vertical-align: top; width: 40%; padding-top: 20px;">
                    <table class="table"
                        style="width: 100%; border-collapse: collapse; border: 2px solid black; font-size: 11px;">
                        <tbody>
                            <tr>
                                <td class="amount-column" style="text-align: left;">Sub Total</td>
                                <td class="amount-column" style="text-align: left; border-right: none;">Rp</td>
                                <td class="amount-column" style="text-align: right; border-left: none;">
                                    {{ number_format($invoice->sub_total ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="amount-column" style="text-align: left;">Diskon</td>
                                <td class="amount-column" style="text-align: left; border-right: none;">Rp</td>
                                <td class="amount-column" style="text-align: right; border-left: none;">
                                    {{ number_format($invoice->diskon ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="amount-column" style="text-align: left;">PPN (11%)</td>
                                <td class="amount-column" style="text-align: left; border-right: none;">Rp</td>
                                <td class="amount-column" style="text-align: right; border-left: none;">
                                    {{ number_format($invoice->ppn ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="amount-column" style="text-align: left;">PBBKB</td>
                                <td class="amount-column" style="text-align: left; border-right: none;">Rp</td>
                                <td class="amount-column" style="text-align: right; border-left: none;">
                                    {{ number_format($invoice->pbbkb ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @if(($invoice->pph ?? 0) > 0)
                            <tr>
                                <td class="amount-column" style="text-align: left;">PPH 23</td>
                                <td class="amount-column" style="text-align: left; border-right: none;">Rp</td>
                                <td class="amount-column" style="text-align: right; border-left: none;">
                                    {{ number_format($invoice->pph ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if(($invoice->oat ?? 0) > 0)
                            <tr>
                                <td class="amount-column" style="text-align: left;">OAT</td>
                                <td class="amount-column" style="text-align: left; border-right: none;">Rp</td>
                                <td class="amount-column" style="text-align: right; border-left: none;">
                                    {{ number_format($invoice->oat ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if(($invoice->transport ?? 0) > 0)
                            <tr>
                                <td class="amount-column" style="text-align: left;">Transport</td>
                                <td class="amount-column" style="text-align: left; border-right: none;">Rp</td>
                                <td class="amount-column" style="text-align: right; border-left: none;">
                                    {{ number_format($invoice->transport ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr style="font-weight: bold; background-color: #f3f3f3;">
                                <td class="amount-column" style="text-align: left; border: 2px solid black;">Total</td>
                                <td class="amount-column"
                                    style="text-align: left; border: 2px solid black; border-right: none;">Rp</td>
                                <td class="amount-column"
                                    style="text-align: right; border: 2px solid black; border-left: none;">
                                    {{ number_format($invoice->total ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- TERBILANG & KETERANGAN -->
        <table style="width: 100%; font-size: 11px; margin-bottom: 30px;">
            <tr>
                <td colspan="3" style="text-align: center;">
                    <strong>TERBILANG</strong>: {{ $invoice->terbilang ?? 'N/A' }}
                </td>
            </tr>
        </table>

        <!-- NOTE BOX -->
        <table style="width: auto; max-width: 60%; font-size: 11px; margin-bottom: 30px; border: 1px solid #000;">
            <tr>
                <td style="background-color: #d3d3d3; padding: 8px; text-align: center; font-weight: bold; border-bottom: 1px solid #000;">
                    NOTE
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; text-align: center;">
                    Bukti pembayaran harap<br>
                    dikirimkan ke email :<br>
                    <strong>indosuksesae@gmail.com</strong>
                </td>
            </tr>
        </table>

        <!-- TANDA TANGAN -->
         <br><br><br><br><br><br><br><br>
        <div style="width: 100%; text-align: right; margin-top: 50px;">
            <div style="display: inline-block; text-align: center;">
                <p style="font-weight: bold; margin-bottom: 60px;">PT INDO ANUGERAH SUKSES ENERGI</p>

               <br><br><br>< <p style="border-bottom: 2px solid black; display: inline-block; padding: 0 20px; font-weight: bold;">
                   KAYLEEN P. SURYA</p>
                <p style="font-size: 11px; margin-top: 4px;">MANAJER KEUANGAN</p>
            </div>
        </div>
</body>

</html>