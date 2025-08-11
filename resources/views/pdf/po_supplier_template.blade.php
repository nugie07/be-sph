<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>PT MINA MARRET TRANS ENERGI INDONESIA - PO SUPPLIER</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 20px;
                background-color: #fff;
                color: #000;
            }

            .footer-title {
                font-size: 10px;
                font-weight: bold;
                text-align: left;
            }
            .footer-name {
                text-align: left;
                font-size: 10px;
                margin-top: 60px; /* Provides space for a signature */
                font-weight: bold;
                text-decoration: underline;
            }
            table p {
                margin: 0;
                font-size: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
            }
            .header-title {
                font-size: 10px;
                font-weight: bold;
                margin: 0;
            }
            .header-content {
                font-size: 10px;
            }

            th,
            td {
                font-size: 10px;
                padding: 5px;
                border: 1px solid #000;
                vertical-align: top;
            }

            p {
                font-size: 10px;
                margin: 0 5px;
            }

            hr {
                border: none;
                border-top: 4px solid #000;
                margin: 5px 0 15px 0;
            }

            li,
            li p {
                font-size: 10px;
                margin: 0;
                padding: 0;
            }

            ol {
                padding-left: 20px;
                margin-top: 0;
            }

            .table-no-border, .table-no-border tr, .table-no-border td {
                border: none;
                padding: 1px 2px;
            }

            .text-right {
                text-align: right;
            }
        </style>
    </head>
    <body>
        <table class="table-no-border">
            <tr>
                <td style="width: 20%; text-align: center; vertical-align: top;">
                    <img src="https://is3.cloudhost.id/bensinkustorage/logo/mina-marret-logo.png" alt="Company Logo" style="height: 50px; margin-bottom: 5px;">
                     <p style="font-size:8px; text-align:center; font-weight:bold; line-height: 1.2;">PT. MINA MARRET TRANS<br>ENERGI INDONESIA</p>
                </td>
                <td style="width: 45%; vertical-align: top; padding-left:10px;">
                    <p class="header-title">Headquarter</p>
                    <p class="header-content">
                            World Capital Tower Lt. 5 Unit 01
                        </p>
                    <p class="header-content">
                            Jl. Mega Kuningan Barat, Lingkar Mega Kuningan No. 3
                        </p>
                    <p class="header-content">
                            Kec. Setiabudi, Jakarta Selatan 12950
                        </p>
                    <p class="header-content">Phone: +62 811 88882221</p>
                </td>
                <td style="width: 35%; vertical-align: top; text-align: right;">
                     <p class="header-title">Banjarmasin Office:</p>
                    <p class="header-content">
                            Gagah Putera Satria Building
                        </p>
                    <p class="header-content">
                        JL KP. Tendean No. 158
                        </p>
                    <p class="header-content">
                            Banjarmasin
                        </p>
                    <p class="header-content">Kalimantan Selatan 70231</p>
                </td>
            </tr>
        </table>

        <hr />

        <h2 style="font-size: 16px; font-weight: bold; text-align: center; margin: 0 0 15px 0; text-decoration: underline;">
                        PURCHASE ORDER
        </h2>

                <table class="table-no-border">
                    <tr>
                <td style="width: 10%;">To</td>
                <td style="width: 45%;">: {{$poTransport->to}}<br>: {{$poTransport->name}}</td>
                <td style="width: 15%;">P.O No.</td>
                <td style="width: 30%;">: {{$poTransport->po_number}}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                <td>P.O Date</td>
                <td>: {{$poTransport->po_date}}</td>
                    </tr>
                    <tr>
                <td>Address</td>
                <td>: {{$poTransport->address}}</td>
                <td>Delivered To</td>
                <td>: {{$poTransport->delivered_to}}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                <td>Loading Point</td>
                <td>: {{$poTransport->loading_point}}</td>
                    </tr>
                </table>

        <p style="margin-left:0; margin-bottom:10px;">Comments or special instructions: {{$poTransport->comments}}</p>

        <table>
            <thead>
                <tr style="text-align:center;">
                    <th style="width: 15%;">REQUESTED BY</th>
                    <th style="width: 20%;">SHIPPED VIA</th>
                    <th style="width: 20%;">F.O.B POINT</th>
                    <th style="width: 20%;">TERM</th>
                    <th style="width: 25%;">TRANSPORT</th>
                </tr>
            </thead>
            <tbody>
                <tr style="text-align:center;">
                    <td>{{$user->name}}</td>
                    <td>{{$poTransport->shipped_via}}</td>
                    <td>{{$poTransport->fob_point}}</td>
                    <td>{{$poTransport->term}} day</td>
                    <td>{{$poTransport->transport}} / liter</td>
                </tr>
            </tbody>
        </table>

        <table>
            <tr style="text-align:center;">
                <th style="width: 15%;">QUANTITY</th>
                <th style="width: 40%; font-weight: bold; text-align: center;">DESCRIPTION</th>
                <th colspan="2" style="width: 20%;">UNIT PRICE (Rp)</th>
                <th colspan="2" style="width: 25%;">AMOUNT</th>
            </tr>
            <tr>
                <td style="text-align:center;">{{$poTransport->quantity}}</td>
                <td style="font-weight: bold; text-align: center;">{{$poTransport->description}}</td>
                <td style="width: 5%; border-right: none;">Rp</td>
                <td style="width: 15%; border-left: none; text-align: right;">{{$poTransport->unit_price}}</td>
                <td style="width: 5%; border-right: none;">Rp</td>
                <td style="width: 20%; border-left: none; text-align: right;">{{$poTransport->amount}}</td>
            </tr>
            <tr>
                <td rowspan="6" colspan="2" style="border: none;"></td>
                <td colspan="2" class="text-right" style="font-weight: bold; border-top: 1px solid #000; border-bottom: none; border-left: none; solid #000; border-right: 1px solid #000;">SUBTOTAL</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: none;">Rp</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: 1px solid #000; text-align: right;">{{$poTransport->sub_total}}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right" style="font-weight: bold; border-top: 1px solid #000; border-bottom: none; border-top: none; border-left: none; solid #000; border-right: 1px solid #000;">PPN 11%</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: none;">Rp</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: 1px solid #000; text-align: right;">{{$poTransport->ppn}}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right" style="font-weight: bold; border-top: 1px solid #000; border-bottom: none; border-top: none; border-left: none; solid #000; border-right: 1px solid #000;">PBBKB 7.5%</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: none;">Rp</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: 1px solid #000; text-align: right;">{{$poTransport->pbbkb}}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right" style="font-weight: bold; border-top: 1px solid #000; border-bottom: none; border-top: none; border-left: none; solid #000; border-right: 1px solid #000;">PPH 22 (0.3%)</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: none;">Rp</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: 1px solid #000; text-align: right;">{{$poTransport->pph}}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right" style="font-weight: bold; border-top: 1px solid #000; border-bottom: none; border-top: none; border-left: none; solid #000; border-right: 1px solid #000;">BPH 0.25%</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: none;">Rp</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: 1px solid #000; text-align: right;">{{$poTransport->bph}}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right" style="font-weight: bold; border: 1px solid #000; border-top: none; border-left: none;border-bottom: none; border-right: 1px solid #000;">TOTAL</td>
                <td style="border: 1px solid #000; border-left: none; border-right: none;">Rp</td>
                <td style="border: 1px solid #000; border-left: none; text-align: right;">{{$poTransport->total}}</td>
            </tr>
        </table>

        <div>
            <p style="margin: 5px 0; font-weight:bold;">Notes:</p>
            <ol style="margin-top: 0; padding-left: 18px;">
                <li>Pesanan yang masuk sesuai dengan harga, jangka waktu, metode pengiriman dan spesifikasi yang tercantum diatas</li>
                <li>Toleransi susut sebesar 0% (Standar Pengukuran menggunakan Flowmeter pada Terminal/ Depo Penebusan)</li>
                <li>Produk sesuai dengan spesifikasi berdasarkan SK Dirjen Migas No. 185.K/HK.02/DJM/2022 (HSD B-40)</li>
                <li style="list-style: none; margin-left: -18px; padding-top: 5px;">
                     <table class="table-no-border" style="margin-bottom:0;">

                        <tr>
                            <td style="width: 15%; padding-left: 0px;">4. NPWP</td>
                            <td style="width: 5%;">:</td>
                            <td style="width: 100%;">94.297.682.0-039.000</td>
                        </tr>
                        <tr>
                            <td style="padding-left: 0px; vertical-align: top;"></td>
                            <td style="vertical-align: top;"></td>
                            <td>World Capital Tower Lt. 5 Unit 01
                                <br>Jl. Mega Kuningan Barat, Lingkar Mega Kuningan No. 3 R.T 5/RW.2
                                <br>Kec. Setiabudi, Jakarta Selatan 12950
                            </td>
                        </tr>

                    </table>
                </li>
                <li style="list-style: none; margin-left: -18px; padding-top: 5px;">
                    <table class="table-no-border" style="margin-bottom:0;">
                        <tr>
                            <td colspan="3" >5. Korespondensi dapat dikirim ke:</td>
                        </tr>
                        <tr>
                            <td style="width: 15%; padding-left: 0px;">Nama</td>
                            <td style="width: 5%;">:</td>
                            <td style="width: 100%;">Murti Endah</td>
                        </tr>
                        <tr>
                            <td style="padding-left: 0px; vertical-align: top;">Alamat</td>
                            <td style="vertical-align: top;">:</td>
                            <td>World Capital Tower Lt. 5 Unit 01
                                <br>Jl. Mega Kuningan Barat, Lingkar Mega Kuningan No. 3 R.T 5/RW.2
                                <br>Kec. Setiabudi, Jakarta Selatan 12950
                            </td>
                        </tr>
                         <tr>
                            <td style="padding-left: 0px;">Phone</td>
                            <td>:</td>
                            <td>+62 811 88882221</td>
                        </tr>
                    </table>
                </li>
            </ol>
        </div>
        <table class="table-no-border" style="margin-top: 20px;">
            <tr>
                <td style="width: 30%; text-align:left;">
                    <p class="footer-title">Authorized by</p>
                    <img style="height: 100px; width:auto; object-fit:contain; margin-bottom: 5px;"
                    src="https://is3.cloudhost.id/bensinkustorage/logo/mina.png"
                    alt=""
                />
                    <p class="footer-name" style="margin-top: 0;">Minasari Mingna</p>
                </td>
                 <td style="width: 70%;"></td>
            </tr>
        </table>
    </body>
</html>
