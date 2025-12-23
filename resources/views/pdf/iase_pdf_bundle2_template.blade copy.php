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
            padding: 16px 26px; /* lebih rapat ke atas dan samping */
            font-size: 11px; /* Diperkecil agar tabel muat dan rata */
            line-height: 1.15;
        }

        /* Header Section */
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 4px; /* lebih rapat */
            margin-bottom: 5px;  /* lebih rapat */
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        .header h4 {
            font-size: 14px; /* perkecil agar muat 3 baris */
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 12px; /* perkecil */
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-bottom: 2px;
        }
        .header small {
            font-size: 9px; /* perkecil */
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
            margin-bottom: 3px; /* rapatkan antar baris paragraf */
        }
        .content table {
            margin-bottom: 10px;
        }

        .kmp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #000;
        }
        .kmp-table th, .kmp-table td {
            vertical-align: middle;
            border: 1px solid #000;
            padding: 4px;
        }
        .kmp-table th {
            font-weight: bold;
            text-align: center;
            background-color: #f2f2f2;
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

        <!-- Top Centered Logo (IASE) -->
        @php
            $logoSrc = 'https://is3.cloudhost.id/bensinkustorage/logo/iase_logo.png';
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
                        <small style="font-size:11px; line-height:1.25;">
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
            <div style="margin-bottom: 4px;"> <!-- lebih rapat jarak block 'Kepada' -->
                <p>Kepada</p>
                <p>{{ $sph->comp_name }}</p>
                <p>Di Sumut</p>
                <p>Up: {{ $sph->pic }}</p>
            </div>
            <div style="margin-bottom: 6px;"> <!-- rapatkan -->
               <br> <p>Dengan hormat,</p>
                <p>Bersama ini kami sampaikan harga penawaran produk kami sebagai berikut:</p>
                <br>Item : {{ $sph->product }}
            </div>

            @php
                // Helper function untuk mencari data berdasarkan nama lokasi dan qty
                $findDetailByLocationAndQty = function($locationName, $qty, $detailsArray) {
                    foreach ($detailsArray as $detail) {
                        if (strcasecmp(trim($detail->cname_lname ?? ''), trim($locationName)) === 0 && 
                            (int)($detail->qty ?? 0) === (int)$qty) {
                            return $detail;
                        }
                    }
                    return null;
                };

                // Convert details collection to array jika belum
                $detailsArray = [];
                if (!empty($details)) {
                    if (is_object($details) && method_exists($details, 'toArray')) {
                        $detailsArray = $details->toArray();
                    } elseif (is_array($details)) {
                        $detailsArray = $details;
                    } else {
                        foreach ($details as $detail) {
                            $detailsArray[] = $detail;
                        }
                    }
                        }
                    @endphp

                    <table style="border-collapse: collapse; width:100%; margin-bottom:4px;">
                <tr>
                    <td style="vertical-align:top; width: 50px; padding-right:6px; white-space:nowrap; font-weight:bold;">Price :</td>
                    <td>
                        <table class="kmp-table" style="border: 1px solid #000;">
                        <colgroup>
                                <col style="width:25%">
                                <col style="width:8%">
                                <col style="width:13%">
                                <col style="width:13%">
                                <col style="width:13%">
                                <col style="width:13%">
                                <col style="width:15%">
                        </colgroup>
                        <thead>
                                <tr>
                                    <th style="text-align:center; border: 1px solid #000;">Lokasi Kalsel</th>
                                    <th style="text-align:center; border: 1px solid #000;">QTY</th>
                                    <th style="text-align:center; border: 1px solid #000;">Harga Dasar</th>
                                    <th style="text-align:center; border: 1px solid #000;">PPN</th>
                                    <th style="text-align:center; border: 1px solid #000;">Total</th>
                                    <th style="text-align:center; border: 1px solid #000;">Transport</th>
                                    <th style="text-align:center; border: 1px solid #000;">Grand Total</th>
                            </tr>
                        </thead>
                        <tbody>
                                @php
                                    $sesulung5 = $findDetailByLocationAndQty('Sesulung Estate', 5, $detailsArray);
                                    $sesulung10 = $findDetailByLocationAndQty('Sesulung Estate', 10, $detailsArray);
                                    $betung5 = $findDetailByLocationAndQty('Desa Betung', 5, $detailsArray);
                                    $betung10 = $findDetailByLocationAndQty('Desa Betung', 10, $detailsArray);
                                @endphp
                                <tr>
                                    <td rowspan="2" style="text-align:center; border: 1px solid #000;">Sesulung Estate</td>
                                    <td style="text-align:center; border: 1px solid #000;">5KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung5 ? number_format((float)$sesulung5->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung5 ? number_format((float)$sesulung5->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung5 ? number_format((float)$sesulung5->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung5 ? number_format((float)$sesulung5->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung5 ? number_format((float)$sesulung5->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; border: 1px solid #000;">10KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung10 ? number_format((float)$sesulung10->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung10 ? number_format((float)$sesulung10->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung10 ? number_format((float)$sesulung10->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung10 ? number_format((float)$sesulung10->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $sesulung10 ? number_format((float)$sesulung10->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="text-align:center; border: 1px solid #000;">Desa Betung</td>
                                    <td style="text-align:center; border: 1px solid #000;">5KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung5 ? number_format((float)$betung5->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung5 ? number_format((float)$betung5->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung5 ? number_format((float)$betung5->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung5 ? number_format((float)$betung5->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung5 ? number_format((float)$betung5->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; border: 1px solid #000;">10KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung10 ? number_format((float)$betung10->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung10 ? number_format((float)$betung10->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung10 ? number_format((float)$betung10->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung10 ? number_format((float)$betung10->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $betung10 ? number_format((float)$betung10->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>

                                <tr>
                                    <th style="text-align:center; background-color:#fff; border: 1px solid #000; font-weight:bold;">Lokasi Kalteng</th>
                                    <th style="text-align:center; border: 1px solid #000;">QTY</th>
                                    <th style="text-align:center; border: 1px solid #000;">Harga Dasar</th>
                                    <th style="text-align:center; border: 1px solid #000;">PPN</th>
                                    <th style="text-align:center; border: 1px solid #000;">Total</th>
                                    <th style="text-align:center; border: 1px solid #000;">Transport</th>
                                    <th style="text-align:center; border: 1px solid #000;">Grand Total</th>
                                </tr>

                                @php
                                    $pundu5 = $findDetailByLocationAndQty('Pundu Pantai Harapan', 5, $detailsArray);
                                    $pundu10 = $findDetailByLocationAndQty('Pundu Pantai Harapan', 10, $detailsArray);
                                    $gunungMas5 = $findDetailByLocationAndQty('Gunung Mas KHS', 5, $detailsArray);
                                    $gunungMas10 = $findDetailByLocationAndQty('Gunung Mas KHS', 10, $detailsArray);
                                    $mustika5 = $findDetailByLocationAndQty('Mustika Sembuluh', 5, $detailsArray);
                                    $mustika10 = $findDetailByLocationAndQty('Mustika Sembuluh', 10, $detailsArray);
                                    $desaAmin5 = $findDetailByLocationAndQty('Desa Amin', 5, $detailsArray);
                                    $desaAmin10 = $findDetailByLocationAndQty('Desa Amin', 10, $detailsArray);
                                    $gunungMakmur5 = $findDetailByLocationAndQty('Gunung Makmur', 5, $detailsArray);
                                    $gunungMakmur10 = $findDetailByLocationAndQty('Gunung Makmur', 10, $detailsArray);
                                    $simpang5 = $findDetailByLocationAndQty('Simpang Seluncing', 5, $detailsArray);
                                    $simpang10 = $findDetailByLocationAndQty('Simpang Seluncing', 10, $detailsArray);
                                @endphp
                                <tr>
                                    <td rowspan="2" style="text-align:center; border: 1px solid #000;">Pundu Pantai Harapan</td>
                                    <td style="text-align:center; border: 1px solid #000;">5KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu5 ? number_format((float)$pundu5->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu5 ? number_format((float)$pundu5->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu5 ? number_format((float)$pundu5->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu5 ? number_format((float)$pundu5->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu5 ? number_format((float)$pundu5->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; border: 1px solid #000;">10KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu10 ? number_format((float)$pundu10->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu10 ? number_format((float)$pundu10->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu10 ? number_format((float)$pundu10->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu10 ? number_format((float)$pundu10->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $pundu10 ? number_format((float)$pundu10->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="text-align:center; border: 1px solid #000;">Gunung Mas KHS</td>
                                    <td style="text-align:center; border: 1px solid #000;">5KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas5 ? number_format((float)$gunungMas5->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas5 ? number_format((float)$gunungMas5->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas5 ? number_format((float)$gunungMas5->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas5 ? number_format((float)$gunungMas5->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas5 ? number_format((float)$gunungMas5->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; border: 1px solid #000;">10KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas10 ? number_format((float)$gunungMas10->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas10 ? number_format((float)$gunungMas10->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas10 ? number_format((float)$gunungMas10->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas10 ? number_format((float)$gunungMas10->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMas10 ? number_format((float)$gunungMas10->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="text-align:center; border: 1px solid #000;">Mustika Sembuluh</td>
                                    <td style="text-align:center; border: 1px solid #000;">5KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika5 ? number_format((float)$mustika5->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika5 ? number_format((float)$mustika5->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika5 ? number_format((float)$mustika5->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika5 ? number_format((float)$mustika5->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika5 ? number_format((float)$mustika5->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; border: 1px solid #000;">10KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika10 ? number_format((float)$mustika10->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika10 ? number_format((float)$mustika10->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika10 ? number_format((float)$mustika10->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika10 ? number_format((float)$mustika10->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $mustika10 ? number_format((float)$mustika10->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="text-align:center; border: 1px solid #000;">Desa Amin</td>
                                    <td style="text-align:center; border: 1px solid #000;">5KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin5 ? number_format((float)$desaAmin5->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin5 ? number_format((float)$desaAmin5->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin5 ? number_format((float)$desaAmin5->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin5 ? number_format((float)$desaAmin5->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin5 ? number_format((float)$desaAmin5->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; border: 1px solid #000;">10KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin10 ? number_format((float)$desaAmin10->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin10 ? number_format((float)$desaAmin10->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin10 ? number_format((float)$desaAmin10->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin10 ? number_format((float)$desaAmin10->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $desaAmin10 ? number_format((float)$desaAmin10->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="text-align:center; border: 1px solid #000;">Gunung Makmur</td>
                                    <td style="text-align:center; border: 1px solid #000;">5KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur5 ? number_format((float)$gunungMakmur5->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur5 ? number_format((float)$gunungMakmur5->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur5 ? number_format((float)$gunungMakmur5->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur5 ? number_format((float)$gunungMakmur5->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur5 ? number_format((float)$gunungMakmur5->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; border: 1px solid #000;">10KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur10 ? number_format((float)$gunungMakmur10->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur10 ? number_format((float)$gunungMakmur10->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur10 ? number_format((float)$gunungMakmur10->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur10 ? number_format((float)$gunungMakmur10->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $gunungMakmur10 ? number_format((float)$gunungMakmur10->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="text-align:center; border: 1px solid #000;">Simpang Seluncing</td>
                                    <td style="text-align:center; border: 1px solid #000;">5KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang5 ? number_format((float)$simpang5->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang5 ? number_format((float)$simpang5->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang5 ? number_format((float)$simpang5->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang5 ? number_format((float)$simpang5->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang5 ? number_format((float)$simpang5->grand_total, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; border: 1px solid #000;">10KL</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang10 ? number_format((float)$simpang10->price_liter, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang10 ? number_format((float)$simpang10->ppn, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang10 ? number_format((float)$simpang10->total_price, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang10 ? number_format((float)$simpang10->transport, 2, ',', '.') : '0,00' }}</td>
                                    <td style="text-align:right; border: 1px solid #000;">{{ $simpang10 ? number_format((float)$simpang10->grand_total, 2, ',', '.') : '0,00' }}</td>
                                        </tr>
                        </tbody>
                        </table>
                    </td>
                </tr>
            </table>
               

            <!-- Payment & Remarks -->
            <div style="margin-top: 10px;">
                <p style="font-weight:bold;">Payment: {{ $sph->pay_method }}</p>
                <p>{{ $settings['Payment_info_1'] ?? '' }}</p>
                <p>{{ $settings['Payment_info_2'] ?? '' }}</p>
                <p>{{ $settings['Payment_info_3'] ?? '' }}</p>
            </div>
            <div class="remarks">
                <span style="font-weight:bold;">Remarks:</span>
                <ol>
                    <li>Toleransi Susut {{ $sph->susut }} % berdasarkan flowmeter yang telah di kalibrasi atau tinggi cairan truk tangki transportir yang sudah di kalibrasi</li>
                    <li><strong>{{ $sph->note_berlaku ?? 'Harga Berlaku' }}</strong> </li>
                    <li>Tanggung Jawab PT IASE terhadap product yang dikirim baik kuantitas maupun kualitas adalah sampai pada saat sebelum bongkar dimana produk masih berada di truk
                        tangki transportir PT IASE . Pelanggan berkewajiban mengambil sampel untuk disimpan dan memastikan produk dalam kondisi baik sebelum dibongkar.
                    </li>
                    <li>Produk sesuai dengan spesifikasi berdasarkan SK Dirjen Migas No. {{ $settings['other_config']['pbbkb_include_sk'] ?? '' }}</li>
                    <li>PO harap dapat diemailkan ke {{ $settings['sph_bundle2_email1'] ?? '' }} dan {{ $settings['pbbkb_include_email2'] ?? '' }}</li>
                    <li>Harap mencantumkan No Tagihan dan No PO pada bukti transfer anda sebagai bukti pembayaran yang sah</li>
                    <li>Harga termasuk <strong>PBBKB</strong></li>
                </ol>
            </div>
            <p>Demikianlah proposal penawaran ini kami buat, bila ada pertanyaan mohon untuk menghubungi kami.</p>
            <p>Terima kasih atas perhatian dan kerjasamanya.</p>
        </div>

        <!-- Footer Section -->
        <div style="margin-top: 16px; width: 100%;">
            <table width="100%" style="border-collapse: collapse;">
            <tr>
            <!-- Kolom Tanda Tangan -->
            <td style="width:60%; vertical-align:bottom;">
            <div>
            Salam Sukses,<br><br><br><br>
            <span style="font-weight:bold;">{{ $settings['other_config']['footer_bundle2'] ?? '' }}</span><br>
            
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
                            <img src="{{ $asibSrc }}" alt="ASIB Logo" style="height:57px;width:auto;display:block;">
                        @endif
                        @if(!empty($gmiSrc))
                            <img src="{{ $gmiSrc }}" alt="GMI Logo" style="height:57px;width:auto;display:block;">
                        @endif
                    </span>

                    <span style="display:inline-block; font-size:8.5px; line-height:1.25; text-align:left; white-space:nowrap; position:relative; top:-18px;">
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
