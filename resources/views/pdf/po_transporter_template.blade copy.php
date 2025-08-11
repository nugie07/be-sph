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
                <td style="width: 10%; border: none">
                    <img
                        src="https://is3.cloudhost.id/bensinkustorage/logo/pertamina.png"
                        alt="Pertamina Logo"
                        class="logo-1"
                    />
                </td>
                <td style="width: 80%; border: none; text-align: center">
                    <p class="title">PT MINA MARRET LINTAS NUSANTARA</p>
                    <p class="sub-title">
                        AGEN RESMI BBM NON-SUBSIDI PERTAMINA
                    </p>
                    <p>
                        Jenis Komoditi/ Produk : Solar HSD B35, MFO, Pertamax,
                        Pertamax Turbo dan Dexlite
                    </p>
                    <p>
                        World Capital Tower 5th Floor, Unit 01 Jl. Mega Kuningan
                        Barat No. 3 Kec. Setiabudi, Jakarta Selatan 12950
                    </p>
                    <p>
                        Gagah Putera Satria Building Jl. KP Tendean No. 158
                        Banjarmasin, Kalimantan Selatan 70231
                    </p>
                    <p>Email : info@mmln.org / Telp. 0811 - 8888 - 0321</p>
                </td>
                @if ($poTransport->type == "MMTEI")
                <td style="width: 10%; border: none">
                    <img
                        src="{{ public_path('static/images/logo/pertamina.png') }}"
                        alt="Pertamina Logo"
                        class="logo-2"
                    />
                </td>
                @endif
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
                <td style="border: none; font-weight: bold; text-align:right">Uang Portal</td>
                <td>Rp. {{$poTransport->portal_money}}</td>
            </tr>
            <tr>
                <td colspan="3" style="border: none"></td>
                <td style="border: none; font-weight: bold; text-align:right">Total</td>
                <td>Rp. {{$poTransport->total}}</td>
            </tr>
        </table>

        <div>
            <p>Notes</p>
            <ol>
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
                    <p class="footer-title">Reqeusted By</p>
                <img style="height: 100px; width:auto; object-fit:contain;"
                    src="https://is3.cloudhost.id/bensinkustorage/logo/aris_sign.png"
                    alt=""
                />
                <p class="footer-name">Aris</p>
                </td>
                <td style="align-items: center; text-align:center">
                    <p class="footer-title">Prepare By</p>
                <img style="height: 100px; width:auto; object-fit:contain;"
                    src="https://is3.cloudhost.id/bensinkustorage/logo/rachman_sign.png"
                    alt=""
                />
                <p class="footer-name">rachman_sign</p>
                </td>
                <td></td>
                <td style="align-items: center; text-align:center" >
                    <p class="footer-title">Authorized By</p>
                    <img style="height: 100px; width:auto; object-fit:contain;"
                        src="https://is3.cloudhost.id/bensinkustorage/logo/tantry_sign.png"
                        alt=""
                    />
                    <p class="footer-name">Tantry Wahyuni </p></td>
            </tr>
        </table>
    </body>
</html>
