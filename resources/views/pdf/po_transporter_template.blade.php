<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>PT MINA MARRET LINTAS NUSANTARA - PO Transporter</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 20px;
            }

            .footer-title {
                font-size: 12px;
                font-weight: bold;
                text-align: center;
                text-decoration: underline;
            }
            .footer-name{
                text-align: center;
                font-size: 12px;
            }
            table p {
                margin: 0 5px;
                font-size: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
            }
            .title {
                font-size: 14px;
                font-style: bold;
                margin: 0 5px;
            }
            .sub-title {
                font-size: 13px;
                font-style: bold;
                margin: 0 5px;
            }
            th,
            td {
                font-size: 12px;
                border: 1px solid #000;
            }

            p {
                font-size: 12px;
                margin: 0 5px;
            }
            .yellow-bg {
                background-color: yellow;
            }
            hr {
                border-bottom: 2px dashed #000;
            }

            li,
            li p {
                font-size: 12px;
            }

            .table-no-border td {
                border: none;
            }
        </style>
    </head>
    <body>
        <table>
            <tr>
                <td style="width: 20%; text-align: center; vertical-align: top; border:none;">
                    <img src="{{ $storage_url }}/logo/mina-marret-logo.png" alt="Company Logo" style="height: 70px; margin-bottom: 5px;">
                     <p style="font-size:8px; text-align:center; font-weight:bold; line-height: 1.2;">PT. MINA MARRET TRANS<br>ENERGI INDONESIA</p>
                </td>
                <td style="width: 80%; border: none; text-align: center">
                    <p class="title">PT MINA MARRET LINTAS NUSANTARA</p>
                    <p class="sub-title">
                        AGEN RESMI BBM NON-SUBSIDI PERTAMINA
                    </p>
                    <p>
                        Jenis Komoditi/ Produk : Solar HSD B40
                    </p>
                    <p>
                        World Capital Tower 5th Floor, Unit 01 Jl. Mega Kuningan
                        Barat No. 3 Kec. Setiabudi, Jakarta Selatan 12950
                    </p>
                    <p>
                        Gagah Putera Satria Building Jl. KP Tendean No. 158
                        Banjarmasin, Kalimantan Selatan 70231
                    </p>
                    <p>Email : info@mmtei.org / Telp. 0811 - 8888 - 2221</p>
                </td>

            </tr>
        </table>
        <hr />
        <table>
            <tr style="text-align: center">
                <td style="border: none">
                    <p
                        style="
                            font-size: 14px;
                            font-weight: bold;
                            text-decoration: underline;
                        "
                    >
                        PURCHASE ORDER
                    </p>
                </td>
            </tr>
        </table>
                <table class="table-no-border">
                    <tr>
                        <td>To :</td>
                        <td>{{$poTransport->to}}</td>
                        <td></td>
                        <td></td>
                        <td>PO. No :</td>
                        <td>{{$poTransport->po_number}}</td>
                    </tr>
                    <tr>
                        <td>Name :</td>
                        <td>{{$poTransport->name}}</td>
                        <td></td>
                        <td></td>
                        <td>PO. Date :</td>
                        <td>{{$poTransport->po_date}}</td>
                    </tr>
                    <tr>
                        <td>Address :</td>
                        <td>{{$poTransport->address}}</td>
                        <td></td>
                        <td></td>
                        <td>Delivery To :</td>
                        <td>{{$poTransport->delivered_to}}</td>
                    </tr>
                    <tr>
                        <td>Phone/fax :</td>
                        <td>{{$poTransport->phone_fax}}</td>
                        <td></td>
                        <td></td>
                        <td>Loading Point :</td>
                        <td>{{$poTransport->loading_point}}</td>
                    </tr>
                </table>
            </div>

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
                <td colspan="2" style="font-weight: bold; border-top: 1px solid #000; border-bottom: none; border-left: none; solid #000; border-right: 1px solid #000; text-align: right;">SUBTOTAL</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: none;">Rp</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: 1px solid #000; text-align: right;">{{$poTransport->sub_total}}</td>
            </tr>
            <tr>
                <td colspan="2" style="font-weight: bold; border-top: 1px solid #000; border-bottom: none; border-top: none; border-left: none; solid #000; border-right: 1px solid #000; text-align: right;">PPN 11%</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: none;">Rp</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: 1px solid #000; text-align: right;">{{$poTransport->ppn}}</td>
            </tr>
            <tr>
                <td colspan="2" style="font-weight: bold; border-top: 1px solid #000; border-bottom: none; border-top: none; border-left: none; solid #000; border-right: 1px solid #000; text-align: right;">Uang Portal</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: none;">Rp</td>
                <td style="border-top: 1px solid #000; border-bottom: none; border-left: none; border-right: 1px solid #000; text-align: right;">{{$poTransport->portal_money}}</td>
            </tr>

            <tr>
                <td colspan="2" style="font-weight: bold; border: 1px solid #000; border-top: none; border-left: none;border-bottom: none; border-right: 1px solid #000; text-align: right;">TOTAL</td>
                <td style="border: 1px solid #000; border-left: none; border-right: none;">Rp</td>
                <td style="border: 1px solid #000; border-left: none; text-align: right;">{{$poTransport->total}}</td>
            </tr>
        </table>

        <div>
            <p style="margin: 5px 0; font-weight:bold;">Notes:</p>
            <ol style="margin-top: 0; padding-left: 18px;">
                <li>Pesanan yang masuk sesuai dengan harga, jangka waktu, metode pengiriman dan spesifikasi yang tercantum diatas</li>
                <li>Toleransi susut sebesar 0,3% (Standar Pengukuran menggunakan Flowmeter)</li>
                <li>Produk sesuai dengan spesifikasi berdasarkan SK Dirjen Migas No. 170.K/HK.02/DJM/2023 (HSD B-35)</li>
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
        <table class="table-no-border">
            <tr>
                <td style="align-items: center; text-align:center">
                    <p class="footer-title">Reqeusted By</p>
                <img style="height: 100px; width:auto; object-fit:contain;"
                    src="{{ $storage_url }}/logo/ramadan.png"
                    alt=""
                />
                <p class="footer-name">M. Ramadan</p>
                </td>
                <td style="align-items: center; text-align:center">
                    <p class="footer-title">Prepare By</p>
                <img style="height: 100px; width:auto; object-fit:contain;"
                    src="{{ $storage_url }}/logo/maulidya.png"
                    alt=""
                />
                <p class="footer-name">Maulidya Dita Iswana</p>
                </td>
                <td></td>
                <td style="align-items: center; text-align:center" >
                    <p class="footer-title">Authorized By</p>
                    <img style="height: 100px; width:auto; object-fit:contain;"
                        src="{{ $storage_url }}/logo/rudyanto.png"
                        alt=""
                    />
                    <p class="footer-name">Rudiyanto </p></td>
            </tr>
        </table>
    </body>
</html>
