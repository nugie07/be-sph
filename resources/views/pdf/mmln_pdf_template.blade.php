<!DOCTYPE html>
<html>

<head>
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

        .table .amount {
            border: 1px solid black;
            text-align: right;
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
    </style>
</head>

<body>
    <div class="header">
			<table>
				<tr>
						<td width="10%">
							@php
								$minaMarretPath = public_path('static/images/logo/logo.png');
								$minaMarretType = pathinfo($minaMarretPath, PATHINFO_EXTENSION);
								$minaMarretData = file_get_contents($minaMarretPath);
								$minaMarretLogoBase64 = 'data:image/' . $minaMarretType . ';base64,' . base64_encode($minaMarretData);

								$pertaminaPath = public_path('static/images/logo/pertamina.png');
								$pertaminaType = pathinfo($pertaminaPath, PATHINFO_EXTENSION);
								$pertaminaData = file_get_contents($pertaminaPath);
								$pertaminaLogoBase64 = 'data:image/' . $pertaminaType . ';base64,' . base64_encode($pertaminaData);
							@endphp
							<img
								src="{{ $minaMarretLogoBase64 }}"
								alt="Logo"
								style="width: 100px; height: 100px; object-fit: contain">

						</td>
						<td width="80%">
								<h4>PT MINA MARRET LINTAS NUSANTARA</h4>
								<p>AGEN RESMI BBM NON-SUBSIDI PERTAMINA</p>
								<small>
										Berdasarkan Surat PT Pertamina Patra Niaga No. 1684/PND900000/2022-S3 Tanggal 22 Desember 2022<br />
										Jenis Komoditi/ Produk: {{ $settings['header_komoditi_produk_mmln'] }}<br />
										World Capital Tower 5th Floor, Unit 01, Jl. Mega Kuningan Barat
										No. 3, Kec. Setiabudi, Jakarta Selatan 12950<br />
										Gagah Putera Satria Building Jl. KP Tendean No. 158 Banjarmasin,
										Kalimantan Selatan 70231<br />
										Email : {{ $settings['contact_info_header_email_mmln'] }} / Telp : {{ $settings['contact_info_header_telp_mmln'] }}
								</small>
						</td>
						<td width="10%">
							<img
								src="{{ $pertaminaLogoBase64 }}"
								alt="Logo"
								style="width: 100px; height: 100px; object-fit: contain">

						</td>
				</tr>
		</table>
    </div>
    <div class="content">
        <table width="100%">
            <tr>
                <td style="width: 75%">Ref : {{ $sph->sph_code }}</td>
                <td style="width: 25%">Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td>
            </tr>
        </table>
        <div style="margin-bottom: 20px">
            <p>Kepada</p>
            <p>{{ $sph->name_company }}</p>
            <p>Di Tempat</p>
            <p>Up: {{ $sph->company_pic }}</p>
        </div>
        <div style="margin-bottom: 20px">
            <p>Dengan hormat,</p>
            <p>
                Bersama ini kami sampaikan harga penawaran produk kami sebagai berikut
                :
            </p>
        </div>
        <table>
            <tr>
                <td style="width: 20px">Item</td>
                <td style="width: 20px">:</td>
                <td>{{ $sph->product_name }}</td>
            </tr>
            <tr>
                <td>Price</td>
                <td>:</td>
                <td>
                    <table class="table" width="100%">
                        <thead>
                            <tr style="padding: 0px; margin: 0px;">
                                <th></th>
                                <th style="border: 1px solid">Harga Produk / liter</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="padding: 0px; margin: 0px;">
                                <td class="label">Rp</td>
                                <td class="amount">{{ number_format($sph->harga, 2, ',', '.') }}</td>
                                <td class="info">/ ltr (Harga Dasar)</td>
                            </tr>
                            <tr style="padding: 0px; margin: 0px;">
                                <td class="label">Rp</td>
                                <td class="amount">{{ number_format($sph->ppn, 2, ',', '.') }}</td>
                                <td class="info">/ ltr (PPN 11%)</td>
                            </tr>
                            <tr style="padding: 0px; margin: 0px;">
                                <td class="label">Rp</td>
                                <td class="amount">{{ number_format($sph->pbbkb, 2, ',', '.') }}</td>
                                <td class="info">/ ltr (PBBKB {{ $sph->percentage_location }}%)</td>
                            </tr>
                            <tr style="padding: 0px; margin: 0px;">
                                <td class="label">Rp</td>
                                <td class="amount" style="font-weight: bold">{{ number_format($sph->total, 2, ',', '.') }}</td>
                                <td class="info">/ ltr (Total Harga Produk)</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td>OAT</td>
                <td>:</td>
                <td style="display: flex;">
                    <table>
                        <tr>
                            @php
                            $dataCount = $customers->count();
                            $showTwoTables = $dataCount >= 10;
                            $half = ceil($dataCount / 2);
                            $leftData = $customers->take($half);
                            $rightData = $customers->slice($half);
                            @endphp

                            @if (!$showTwoTables)
                            <td style="padding-right: 20px;">
                                <table class="tableoat">
                                    <thead>
                                        <tr>
                                            <th>Lokasi</th>
                                            <th>Qty</th>
                                            <th>OAT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($customers as $customer)
                                        <tr style="padding: 0px; margin: 0px;">
                                            <td>{{ $customer->lokasi }}</td>
                                            <td>{{ $customer->qty }}</td>
                                            <td>Rp {{ number_format($customer->harga, 2, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                            @else
                            <td style="padding-right: 20px;">
                                <table class="tableoat">
                                    <thead>
                                        <tr>
                                            <th>Lokasi</th>
                                            <th>Qty</th>
                                            <th>OAT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($leftData as $customer)
                                        <tr style="padding: 0px; margin: 0px;">
                                            <td>{{ $customer->lokasi }}</td>
                                            <td>{{ $customer->qty }}</td>
                                            <td>Rp {{ number_format($customer->harga, 2, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                            <td style="padding-left: 20px;">
                                <table class="tableoat">
                                    <thead>
                                        <tr>
                                            <th>Lokasi</th>
                                            <th>Qty</th>
                                            <th>OAT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rightData as $customer)
                                        <tr style="padding: 0px; margin: 0px;">
                                            <td>{{ $customer->lokasi }}</td>
                                            <td>{{ $customer->qty }}</td>
                                            <td>Rp {{ number_format($customer->harga, 2, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                            @endif
                        </tr>
                    </table>
                </td>
            </tr>

        </table>

        <div style="margin-top: -30px;">
					<p>Payment: {{ $sph->payment_method }}</p>
					<p>Pembayaran dapat ditransfer ke :</p>
					<p>{{ $settings['mmln_peyment_to'] }}</p>
					<p class="mb-32">{{ $settings['mmln_bank_account_info'] }}</p>
				</div>

        <div class="remarks" style="margin-top: -10px;">
            <strong>Remarks :</strong>
            <ol>
                @foreach ($remarks as $remark)
                <li style="margin-top: -10px;">
                    {{ $remark->remarks_value }}
                </li>
                @endforeach
            </ol>
        </div>
        <p>
            Demikianlah proposal penawaran ini kami buat, bila ada pertanyaan mohon
            untuk menghubungi kami.
        </p>
        <p>Terima kasih atas perhatian dan kerjasamanya</p>

        <table width="100%" style="margin-top: 10px;">
					<tr>
							<td style="width: 55%">
									<p>Salam Sukses,</p>
									<p>{{ $settings['report_contact_name_info'] }}</p>
									<p style="color:#0066cc">HP : {{ $settings['report_contact_telp_info'] }}</p>
							</td>
							<td style="width: 45%">
								<div class="footer">
									<table style="width: 100%; border-collapse: collapse;">
											<tr>
													<td style="text-align: center;">
															@php
																	$Path = public_path('static/images/logo/logo-ISO.jpg');
																	$Type = pathinfo($Path, PATHINFO_EXTENSION);
																	$Data = file_get_contents($Path);
																	$isoBase64 = 'data:image/' . $Type . ';base64,' . base64_encode($Data);
															@endphp
															<img src="{{ $isoBase64 }}" alt="ISO Logo" style="width: 100px; height: 50px; object-fit: contain;">
													</td>
													<td style="padding-left: 15px;">
															<div style="margin-top: 8px;">
																<p style="margin: 0;">ISO 9001:2015 No. GMIQ2311099</p>
																<p style="margin: 0;">ISO 14001:2015 No. GMIE2311100</p>
																<p style="margin: 0;">ISO 45001:2018 No. GMIO2311101</p>
															</div>
													</td>
											</tr>
									</table>
								</div>
							</td>
					</tr>
			</table>
    </div>
</body>

</html>
