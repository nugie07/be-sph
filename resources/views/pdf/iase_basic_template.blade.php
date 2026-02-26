<!DOCTYPE html>
<html>
<head>
    <title>Surat Penawaran Harga</title>
    <style>
        /* Mengatur dasar dokumen agar tidak ada margin/padding tak terduga */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Mengatur font, ukuran, dan padding utama untuk body agar sesuai dengan kertas A4 */
        body {
            font-family: Arial, Helvetica, sans-serif; /* Menggunakan font sans-serif standar */
            background-color: #fff;
            margin: 0;
            padding: 34px 36px;
            font-size: 11px; /* Ukuran font dasar sedikit diperkecil untuk memastikan muat 1 halaman */
            line-height: 1.15;
        }

        /* Header Section */
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-top: 0;
            margin-bottom: 5px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        .header small {
            font-size: 11px; /* Ukuran disesuaikan */
            color: #333;
            line-height: 1.3;
            display: block;
            font-weight: normal;
        }

        /* Content Section */
        .content {
            font-size: 11px;
        }
        .content p {
            margin-bottom: 4px;
        }
        .content table {
            margin-bottom: 8px;
        }

        /* Tabel Harga Utama */
        .table {
            border-collapse: collapse;
        }
        .table th, .table td {
            font-size: 11px;
        }
        .table th {
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }
        .table td {
            padding: 2px 3px;
        }
        .table .amount {
            border: 1px solid black;
            text-align: right;
            padding-right: 5px;
        }
        .table .label {
            text-align: left;
        }
        .table .info {
            padding-left: 10px;
        }

        /* Tabel OAT (Ongkos Angkut) */
        .tableoat {
            border-collapse: collapse;
        }
        .tableoat th, .tableoat td {
            font-size: 11px;
            border: 1px solid black;
            padding: 3px;
        }
        .tableoat th {
            text-align: center;
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .tableoat td:nth-child(2),
        .tableoat td:nth-child(3) {
            text-align: center;
        }

        /* Remarks Section */
        .remarks {
            margin-top: 8px;
        }
        .remarks ol {
            padding-left: 20px; /* Mengurangi padding agar lebih rapi */
            margin: 4px 0;
        }
        .remarks ol li {
            margin-bottom: 2px;
            line-height: 1.3;
        }

        /* Box wrapper agar Price & OAT sama lebar dan sejajar */
        .box-wrap {
            width: 320px; /* samakan lebar kotak seperti contoh */
        }

    </style>
</head>

<body>
    <!-- Wrapper utama untuk memastikan konten tidak keluar halaman -->
    <div style="width: 100%; height: 100%;">

        <!-- Top Centered Logo (IASE) -->
        @php
            $logoSrc = $storage_url . '/logo/iase_logo.png';
        @endphp
        <div style="text-align:center; margin-bottom: 0;">
            @if(!empty($logoSrc))
                <!-- FIX: Mengurangi tinggi logo agar tidak memakan banyak tempat -->
                <img src="{{ $logoSrc }}" alt="Logo" style="height:85px; width:auto; object-fit:contain;">
            @else
                <div style="height:85px; width:180px; border:1px solid #ccc; display:inline-flex; align-items:center; justify-content:center; font-size:10px;">LOGO</div>
            @endif
        </div>
        <div class="header">
            <table>
                <tr>
                    <td width="100%" style="text-align:center; vertical-align:middle;">
                        <small style="font-size:13px;">
                            Jenis Komoditi/ Produk: Solar HSD B40<br />
                            World Capital Tower 5th Floor, Unit 01, Jl. Mega Kuningan Barat No. 3, Kec. Setiabudi, Jakarta Selatan 12950<br />
                            Gagah Putera Satria Building Jl. KP Tendean No. 158 Banjarmasin, Kalimantan Selatan 70231<br />
                            {{ $settings['Sub_Title_4'] ?? '' }}
                        </small>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Content -->
        <div class="content">
            <table width="100%">
                <tr>
                    <td style="width: 75%;">Ref : {{ $sph->kode_sph }}</td>
                    <td style="width: 25%;">Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td>
                </tr>
            </table>
            <div style="margin-bottom: 10px;">
                <p>Kepada</p>
                <p>{{ $sph->comp_name }}</p>
                <p>Di Tempat</p>
                
            </div>
            <div style="margin-bottom: 10px;">
                <p>Dengan hormat,</p>
                <p>Bersama ini kami sampaikan harga penawaran produk kami sebagai berikut:</p>
            </div>

            <!-- Tabel Utama (Item, Price, OAT) -->
            <table width="100%" style="border-collapse: collapse;">
                <tr>
                    <td style="width: 8%; vertical-align: top;">Item</td>
                    <td style="width: 2%; vertical-align: top;">:</td>
                    <td style="vertical-align: top;">{{ $sph->product }}</td>
                </tr>
                <tr>
                    <td style="vertical-align: top;">Price</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="vertical-align: top;">
                        <div class="box-wrap">
                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th style="border: 1px solid">Harga Produk / liter</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="label">Rp</td>
                                    <td class="amount">{{ number_format($sph->price_liter, 2, ',', '.') }}</td>
                                    <td class="info">/ ltr (Harga Dasar)</td>
                                </tr>
                                <tr>
                                    <td class="label">Rp</td>
                                    <td class="amount">{{ number_format($sph->ppn, 2, ',', '.') }}</td>
                                    <td class="info">/ ltr (PPN 11%)</td>
                                </tr>
                                <tr>
                                    <td class="label">Rp</td>
                                    <td class="amount" style="font-weight: bold;">{{ number_format($sph->total_price, 2, ',', '.') }}</td>
                                    <td class="info">/ ltr (Total Harga Produk)</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top;">OAT</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="vertical-align: top;">
                        @php
                            $details = $details ?? ($data['details'] ?? collect());
                            // Pastikan berupa collection agar mudah diolah
                            $details = collect($details);
                        @endphp
                        <div class="box-wrap">
                        <table class="tableoat" style="width: 100%; margin-bottom: 4px;">
                            <colgroup>
                                <col style="width: 60%">
                                <col style="width: 20%">
                                <col style="width: 20%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Lokasi {{ strtoupper($sph->biaya_lokasi ?? 'KALSEL') }}</th>
                                    <th>OAT 10KL</th>
                                    <th>OAT 5KL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($details as $d)
                                    <tr>
                                        <td>{{ $d->cname_lname ?? ($d['cname_lname'] ?? '') }}</td>
                                        <td style="text-align:right;">{{ number_format((float)($d->total_price ?? ($d['total_price'] ?? 0)), 2, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format((float)($d->grand_total ?? ($d['grand_total'] ?? 0)), 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="text-align:center;">Tidak ada data OAT</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div style="font-size:10px; margin-top:2px;">*OAT tidak kena PPN</div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Payment & Remarks -->
            <div style="margin-top: 8px;">
                <p style="font-weight:bold;">Payment: {{ $sph->pay_method }}</p>
                <p>{{ $settings['Payment_info_1'] ?? '' }}</p>
                <p>{{ $settings['Payment_info_2'] ?? '' }}</p>
                <p>{{ $settings['Payment_info_3'] ?? '' }}</p>
            </div>
            <div class="remarks">
                <span style="font-weight:bold;">Remarks:</span>
                <ol>
                    <li>Toleransi susut sebesar {{ $sph->susut }} % berdasarkan flowmeter yang telah di kalibrasi atau
                         tinggi cairan truk tangki transportir yang telah di kalibrasi</li>
                    <li>Harga berlaku dari <strong>{{ $sph->note_berlaku ?? '' }}</strong> </li>
                    <li>Tanggung jawab PT IASE terhadap product yang dikirim baik kuantitas maupun kualitas adalah sampai pada saat sebelum bongkar dimana produk masih berada di truk
                        tangki transportir PT IASE . Pelanggan berkewajiban mengambil sampel untuk disimpan 
                        dan memastikan produk dalam kondisi baik sebelum dibongkar.</li>
                    <li>Produk sesuai dengan spesifikasi berdasarkan SK Dirjen Migas No. {{ $settings['other_config']['pbbkb_include_sk'] ?? '' }}</li>
                    <li>PO harap dapat diemailkan ke {{ $email->useremail ?? '' }} dan {{ $settings['pbbkb_include_email2'] ?? '' }}</li>
                    <li>Harga sewaktu-waktu dapat berubah tanpa pemberitahuan terlebih dahulu.</li>
                    <li>Harap mencantumkan No Tagihan dan No PO pada bukti transfer anda sebagai bukti pembayaran yang sah</li>
                </ol>
            </div>
            <p>Demikianlah proposal penawaran ini kami buat, bila ada pertanyaan mohon untuk menghubungi kami.</p>
            <p>Terima kasih atas perhatian dan kerjasamanya.</p>
        </div>

        <!-- Footer Section -->
        <div style="position: absolute; bottom: 40px; left: 36px; right: 36px; width: auto;">
            <table width="100%" style="border-collapse: collapse;">
            <tr>
            <!-- Kolom Tanda Tangan -->
            <td style="width:60%; vertical-align:bottom;">
            <div>
            Salam Sukses,<br><br><br><br>
            <span style="font-weight:bold;">{{ $email->first_name ?? '' }} {{ $email->last_name ?? '' }}</span>
            </div>
            </td>
            <!-- Kolom Logo dan ISO (disejajarkan ke kanan) -->
            <td style="width:40%; vertical-align:bottom; text-align:right;">
                <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:14px;">

                    <span style="display:inline-flex; align-items:center; gap:8px;">
                        @php
                            $asibSrc = $asibLogoBase64 ?? ($settings['other_config']['ASIBLogoBase64'] ?? null);
                            $gmiSrc  = $gmiLogoBase64 ?? ($settings['other_config']['GMILogoBase64'] ?? null);
                        @endphp
                        @if(!empty($asibSrc))
                            <img src="{{ $asibSrc }}" alt="ASIB Logo" style="height:52px;width:auto;display:block;">
                        @endif
                        @if(!empty($gmiSrc))
                            <img src="{{ $gmiSrc }}" alt="GMI Logo" style="height:52px;width:auto;display:block;">
                        @endif
                    </span>

                    <span style="display:inline-block; font-size:9px; line-height:1.25; text-align:left; white-space:nowrap; position:relative; top:-25px;">
                        <span style="display:block; margin:0;">ISO 9001 : 2015 No. GMIQ2311099</span>
                        <span style="display:block; margin:0;">ISO 14001 : 2015 No. GMIE2311100</span>
                        <span style="display:block; margin:0;">ISO 45001 : 2018 No. GMIO2311101</span>
                    </span>
                </div>
            </td>
            </tr>
            </table>
        </div>
    </div>
</body>
</html>
