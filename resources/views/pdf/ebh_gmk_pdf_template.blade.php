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
            font-size: 12px; /* Ukuran font dasar sedikit diperkecil untuk memastikan muat 1 halaman */
            line-height: 1.15;
        }

        /* Header Section */
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
            font-size: 16px; /* Ukuran disesuaikan */
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
        }
        .header p {
            font-size: 14px; /* Ukuran disesuaikan */
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-bottom: 3px;
        }
        .header small {
            font-size: 10px; /* Ukuran disesuaikan */
            color: #333;
            line-height: 1.3;
            display: block;
            font-weight: normal;
        }

        /* Content Section */
        .content {
            font-size: 12px;
        }
        .content p {
            margin-bottom: 5px;
        }
        .content table {
            margin-bottom: 10px;
        }

        /* Tabel Harga Utama */
        .table {
            border-collapse: collapse;
        }
        .table th, .table td {
            font-size: 12px;
        }
        .table th {
            padding: 6px;
            text-align: center;
            font-weight: bold;
        }
        .table td {
            padding: 3px;
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
            font-size: 12px;
            border: 1px solid black;
            padding: 4px;
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
            margin-top: 10px;
        }
        .remarks ol {
            padding-left: 20px; /* Mengurangi padding agar lebih rapi */
            margin: 5px 0;
        }
        .remarks ol li {
            margin-bottom: 2px;
            line-height: 1.4;
        }

    </style>
</head>

<body>
    <!-- Wrapper utama untuk memastikan konten tidak keluar halaman -->
    <div style="width: 100%; height: 100%;">

        <!-- Header -->
        <div class="header">
            <table>
                <tr>
                    <td width="12%" style="vertical-align:top;">
                    @php
                            $logoSrc = $storage_url . '/logo/mina-marret-logo.png';
                        @endphp
                        @if(!empty($logoSrc))
                            <img src="{{ $logoSrc }}" alt="Logo" style="width:90px; height:auto; object-fit:contain; display:block; margin:0 auto;">
                        @else
                            <div style="width:90px;height:90px;border:1px solid #ccc;font-size:9px;display:flex;align-items:center;justify-content:center;">LOGO</div>
                        @endif
                    </td>
                    <td width="88%" style="text-align:center; vertical-align:middle;">
                        <h4>PT MINA MARRET TRANS ENERGI INDONESIA</h4>
                        <p>AGEN BBM INDUSTRI</p>
                        <small>
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
            <div style="margin-bottom: 15px;">
                <p>PT Energi Batu Hitam</p>
                <p>PT Indexim</p>
                <p>PT Unggul Dinamika Utama</p>
                <p>PT Bukit Baiduri Energi</p>
                <p>PT Kutai Mineral</p>
                <p>Sahid Sudirman Tower</p>
                <p>Jakarta Pusat</p>
              
            <div style="margin-bottom: 15px;">
                <p>Dengan hormat,</p>
                <p>Bersama ini kami sampaikan harga penawaran produk kami sebagai berikut:</p>
            </div>

            <!-- Tabel Utama (Item, Price, OAT) -->
            <table width="100%" style="border-collapse: collapse;">
                <tr>
                    <td style="width: 8%; vertical-align: top;">Item</td>
                    <td style="width: 2%; vertical-align: top;">:</td>
                    <td style="vertical-align: top; font-weight: bold;">{{ $sph->product }}</td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: bold;">Price</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="vertical-align: top;">
                        <!-- FIX: Mengubah lebar tabel harga menjadi otomatis -->
                        <table class="table" style="width: auto;">
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
                                    <td class="amount">{{ number_format($sph->pbbkb, 2, ',', '.') }}</td>
                                    <td class="info">/ ltr (PBBKB {{ $sph->biaya_lokasi }}%)</td>
                                </tr>
                                <tr>
                                    <td class="label">Rp</td>
                                    <td class="amount" style="font-weight: bold;">{{ number_format($sph->total_price, 2, ',', '.') }}</td>
                                    <td class="info">/ ltr (Total Harga Produk)</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: bold;">OAT</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="vertical-align: top;">
                        @php
                        // Ambil data dari details atau customers
                        $oatData = $details ?? ($customers ?? collect());
                        
                        // Convert ke collection jika belum
                        if (!is_object($oatData) || !method_exists($oatData, 'groupBy')) {
                            $oatData = collect($oatData ?? []);
                        }
                        
                        // Group data berdasarkan lokasi (biaya_lokasi atau location)
                        $groupedByLocation = [];
                        
                        foreach ($oatData as $item) {
                            // Convert object to array jika perlu
                            $itemArray = is_object($item) ? (array) $item : $item;
                            
                            // Ambil lokasi dari biaya_lokasi atau location atau cname_lname
                            $location = $itemArray['biaya_lokasi'] ?? $itemArray['location'] ?? $itemArray['cname_lname'] ?? '';
                            
                            if (empty($location)) continue;
                            
                            // Inisialisasi lokasi jika belum ada
                            if (!isset($groupedByLocation[$location])) {
                                $groupedByLocation[$location] = [
                                    'oat_10kl' => null
                                ];
                            }
                            
                            // Cek type dari berbagai field yang mungkin ada
                            // Format bisa: "46 OAT 10 KL", "OAT 10 KL", dll
                            $product = $itemArray['product'] ?? '';
                            $identifier = $itemArray['identifier'] ?? '';
                            $typeField = $itemArray['type'] ?? '';
                            $cnameLname = $itemArray['cname_lname'] ?? '';
                            
                            // Gabungkan semua kemungkinan field untuk deteksi type
                            $typeString = strtoupper(trim($identifier . ' ' . $product . ' ' . $typeField . ' ' . $cnameLname));
                            
                            $totalPrice = $itemArray['total_price'] ?? 0;
                            
                            // Deteksi type OAT 10 KL (dengan berbagai variasi penulisan)
                            // Data tetap diambil dari oat_10kl, tapi label ditampilkan sebagai "OAT"
                            if (stripos($typeString, 'OAT 10 KL') !== false || 
                                stripos($typeString, 'OAT 10KL') !== false || 
                                stripos($typeString, 'OAT10KL') !== false ||
                                stripos($typeString, 'OAT 10') !== false) {
                                $groupedByLocation[$location]['oat_10kl'] = $totalPrice;
                            }
                        }
                        @endphp
                        
                        <table class="tableoat" style="width: auto; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #000; padding: 5px; text-align: center;">Lokasi</th>
                                    <th style="border: 1px solid #000; padding: 5px; text-align: center;">OAT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($groupedByLocation as $location => $data)
                                <tr>
                                    <td style="border: 1px solid #000; padding: 5px; text-align: center;">{{ $location }}</td>
                                    <td style="border: 1px solid #000; padding: 5px; text-align: center;">
                                        @if($data['oat_10kl'] !== null)
                                            Rp {{ number_format($data['oat_10kl'], 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Tidak ada data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Payment & Remarks -->
            <div style="margin-top: 10px;">
                <p style="font-weight:bold;">Termin Pembayaran: {{ $sph->pay_method }} dari diterimanya tagihan </p>
                <p>{{ $settings['Payment_info_1'] ?? '' }}</p>
                <p>{{ $settings['Payment_info_2'] ?? '' }}</p>
                <p>{{ $settings['Payment_info_3'] ?? '' }}</p>
            </div>
            <div class="remarks">
                <span style="font-weight:bold;">Remarks:</span>
                <ol>
                    <li>Toleransi Susut {{ $sph->susut }} %  (Standar Pengukuran menggunakan Flowmeter/Sounding). Perbandingan Flowmeter/Sounding supplier
                    dengan Flowmeter/Sounding milik customer yang telah dikalibrasi.</li>
                    <li>Harga berlaku dari <strong>{{ $sph->note_berlaku ?? '' }}</strong> </li>
                    <li>Tanggung jawab PT MMTEI terhadap produk yang dikirim baik kuantitas maupun kualitas 
                        adalah sampai pada saat sebelum bongkar dimana produk masih berada di truk PT MMTEI. 
                        Pelanggan berkewajiban mengambil sampel untuk disimpan dan 
                        memastikan produk dalam kondisi baik sebelum dibongkar</li>
                    <li>Produk sesuai dengan spesifikasi berdasarkan SK Dirjen Migas No. {{ $settings['other_config']['pbbkb_include_sk'] ?? '' }}</li>
                    <li>PO harap dikirimkan ke email 
                        @if(!empty($email->useremail))
                            <a href="mailto:{{ $email->useremail }}" style="color: #0000EE; text-decoration: underline;">{{ $email->useremail }}</a>
                        @endif
                        @if(!empty($email->useremail))
                             dan 
                        @endif
                        <a href="mailto:mina@minamarretenergi.com" style="color: #0000EE; text-decoration: underline;">mina@minamarretenergi.com</a>
                    </li>
                    <li>Harga sewaktu waktu dapat berubah tanpa ada pemberitahuan terlebih dahulu</li>
                    <li>Harap mencantumkan No Tagihan dan No PO pada bukti transfer anda sebagai bukti pembayaran yang sah</li>
                    
                </ol>
            </div>
            <p>Demikianlah proposal penawaran ini kami buat, bila ada pertanyaan mohon untuk menghubungi kami.</p>
            <p>Terima kasih atas perhatian dan kerjasamanya.</p>
        </div>

        <!-- Footer Section -->
        <div style="position: absolute; bottom: 38px; left: 36px; right: 36px; width: auto;">
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
