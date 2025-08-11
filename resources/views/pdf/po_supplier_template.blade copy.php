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
            }

            .footer-title {
                font-size: 10px;
                font-weight: bold;
                text-align: center;
                text-decoration: underline;
            }
            .footer-name{
                text-align: center;
                font-size: 10px;
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
            .header {
                font-size: 12px;
                margin: 0 0;
            }
            .title {
                font-style: bold;
            }
            th,
            td {
                font-size: 10px;
                border: 1px solid #000;
            }

            p {
                font-size: 10px;
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
                font-size: 10px;
            }

            .table-no-border td {
                border: none;
            }
            .logo-1{
                display: block;
margin-left: auto;
margin-right: auto;
height: 70px;
            }
        </style>
    </head>
    <body>
        <table>
            <tr>
                <td style="width: 20%; border: none; text-align:center">
                    <img
                        src="https://is3.cloudhost.id/bensinkustorage/logo/pertamina.png"
                        alt="Pertamina Logo"
                        class="logo-1"
                    />
                    <p style="font-size:8px; text-align:center">PT MINA MARRET TRANS ENERGI INDONESIA</p>
                </td>
                <td style="width: 50%; border: none;">
                    <div class="header">
                        <p class="titel">Headquarter</p>

                        <p>
                            World Capital Tower Lt. 5 Unit 01
                        </p>
                        <p>
                            Jl. Mega Kuningan Barat, Lingkar Mega Kuningan No. 3
                        </p>
                        <p>
                            Kec. Setiabudi, Jakarta Selatan 12950
                        </p>
                        <p>Phone: +62 811 88882221</p>
                    </div>
                </td>
                <td style="width: 30%; border: none">
                    <div class="header">
                        <p class="title">Banjarmasin Office:</p>

                        <p>
                            Gagah Putera Satria Building
                        </p>
                        <p>
                            JL. KP. Tendean No. 158
                        </p>
                        <p>
                            Banjarmasin
                        </p>
                        <p>Kalimantan Selatan 70231</p>
                    </div>
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
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>Loading Point :</td>
                        <td>{{$poTransport->loading_point}}</td>
                    </tr>
                </table>
            </div>

        <p>Comments or special instructions : {{$poTransport->comments}}</p>

        <table>
            <thead>
                <tr>
                    <th>REQUESTED BY</th>
                    <th>SHIPPED VIA</th>
                    <th>F.O.B POINT</th>
                    <th>TERM</th>
                    <th>Transport</th>
                </tr>
            </thead>

            <tbody>
                <tr class="">
                    <td>{{$user->name}}</td>
                    <td>{{$poTransport->shipped_via}}</td>
                    <td>{{$poTransport->fob_point}}</td>
                    <td>{{$poTransport->term}} day</td>
                    <td>Rp. {{$poTransport->transport}}</td>
                </tr>
            </tbody>
        </table>

        <table>
            <tr>
                <th>QUANTITY</th>
                <th colspan="2">DESCRIPTION</th>
                <th>UNIT PRICE (Rp)</th>
                <th>AMOUNT</th>
            </tr>
            <tr class="red-bg">
                <td>{{$poTransport->quantity}}</td>
                <td colspan="2">{{$poTransport->description}}</td>
                <td>Rp. {{$poTransport->unit_price}}</td>
                <td>Rp. {{$poTransport->amount}}</td>
            </tr>
            <tr>
                <td colspan="3" style="border: none"></td>
                <td style="border: none; font-weight: bold; text-align:right">SUBTOTAL</td>
                <td>Rp. {{$poTransport->sub_total}}</td>
            </tr>
            <tr>
                <td colspan="3" style="border: none"></td>
                <td style="border: none; font-weight: bold; text-align:right">PPN 11%</td>
                <td>Rp. {{$poTransport->ppn}}</td>
            </tr>
            <tr>
                <td colspan="3" style="border: none"></td>
                <td style="border: none; font-weight: bold; text-align:right">PBBKB 7.5%</td>
                <td>Rp. {{$poTransport->pbbkb}}</td>
            </tr>
            <tr>
                <td colspan="3" style="border: none"></td>
                <td style="border: none; font-weight: bold; text-align:right">PPH 22 (0.3%)</td>
                <td>Rp. {{$poTransport->pph22}}</td>
            </tr>
            <tr>
                <td colspan="3" style="border: none"></td>
                <td style="border: none; font-weight: bold; text-align:right">BPH 0.25%</td>
                <td>Rp. {{$poTransport->bph}}</td>
            </tr>
            <tr>
                <td colspan="3" style="border: none"></td>
                <td style="border: none; font-weight: bold; text-align:right">Total</td>
                <td>Rp. {{$poTransport->total}}</td>
            </tr>
        </table>

        <div>
            <p>Notes</p>
            <ol style="margin-top: 0">
                <li>Harap mengirimkan 2 salinan invoice.</li>
                <li>
                    Pesanan yang masuk sesuai dengan harga, jangka waktu, metode
                    pengiriman dan spesifikasi yang tercantum diatas
                </li>
                <li>
                    Toleransi susut sebesar 0,5% (Standar Pengukuran menggunakan
                    Flowmeter) Perbandingan flowmeter supplier dengan Flowmeter
                    milik customer yang telah dikalibrasi di lapangan
                </li>
                <li>
                    Produk sesuai dengan spesifikasi berdasarkan SK Dirjen Migas
                    No. 170.K/HK.02/DJM/2023 (HSD B-35)
                </li>
                <li>
                    <p>NPWP : 94.438.325.6-039.000</p>
                    <p>PT. MINA MARRET LINTAS NUSANTARA APARTEMEN METRO PARK</p>
                    <p>
                        JL PILAR MAS KAV 28 UNIT MA-LG KEDOYA SELATAN, KEBON
                        JERUK
                    </p>
                    <p>JAKARTA BARAT DKI JAKARTA</p>
                </li>
                <li>
                    <p>Korespondensi dapat dikirim ke :</p>
                    <p>Nama : Tantry</p>
                    <p>Alamat : World Capital Tower Lt. 5 Unit 01</p>
                    <p>
                        Jl. DR Ide Anak Agung Gde Lot D, Lingkar Mega Kuningan
                    </p>
                    <p>Jakarta Selatan, DKI Jakarta 12950</p>
                    <p>Telephone : +62-812-95914415</p>
                </li>
            </ol>
        </div>
        <table class="table-no-border">
            <tr>
                <td style="align-items: center; text-align:center">
                    <p class="footer-title">Prepare By</p>
                <img style="height: 100px; width:auto; object-fit:contain;"
                    src="https://is3.cloudhost.id/bensinkustorage/logo/tantry_sign.png"
                    alt=""
                />
                <p class="footer-name">Tantry Wahyuni</p>
                </td>
                <td></td>
                <td style="align-items: center; text-align:center" >
                    <p class="footer-title">Authorized By</p>
                    <img style="height: 100px; width:auto; object-fit:contain;"
                        src="https://is3.cloudhost.id/bensinkustorage/logo/mina.png"
                        alt=""
                    />
                    <p class="footer-name">Minasari Mingna </p></td>
            </tr>
        </table>
    </body>
</html>
