<!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>PT MINA MARRET TRANS ENERGI INDONESIA - Delivery Note</title>
	<style>
		body {
			font-family: Arial, sans-serif;
		}

		table p {
			font-size: 10px;
			margin: 5px 0;
		}

		.signature {
			width: 100%
		}

		.signature td {
			border: 1px solid
		}

		.shipping {
			width: 100%
		}

		.shipping td {
			border: 1px solid
		}
	</style>
</head>

<body>
	<!-- Header Table -->
	<table width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td width="15%" style="vertical-align: top; padding-right: 10px;">
				<img src="{{ $storage_url }}/logo/mina-marret-logo.png" alt="Logo" style="width: 100px; height: auto;">
			</td>
			<td width="85%" style="vertical-align: middle;">
				<h2 style="margin: 0; padding: 0; font-size: 22px; font-weight: bold;">PT MINA MARRET TRANS ENERGI INDONESIA</h2>
				<p style="margin: 5px 0 0 0; font-size: 11px; line-height: 1.5;">
					World Capital Tower Lt. 05 Unit 01, Jl. DR. Ide Anak Agung Gde Lot D. Lingkar Mega Kuningan Jakarta Selatan, DKI Jakarta 12950<br/>
					Gagah Putera Satria Building, Jl. KP. Tendean No. 158 Banjarmasin, Kalimantan Selatan 70231<br/>
					Email : info@mmtei.com / Telp. 0811 - 8888 - 2221
				</p>
			</td>
		</tr>
	</table>
	<hr>
	<table width="100%">
		<tr>
			<td align="center">
				<p style="margin: 0; font-size: 18px; font-weight: bold; text-decoration: underline;">DELIVERY NOTE</p>
				<p style="margin: 5px 0 0 0; font-size: 14px; font-weight: bold;">BBM Non Subsidi</p>
			</td>
		</tr>
	</table>

	<br />

	<!-- Delivery Information Table -->
	<table width="100%" cellspacing="0" cellpadding="5" style="font-size:14px;">
		<tr>
			<td width="60%">
				<table style="border: none">
					<tr>
						<td>NO</td>
						<td>:</td>
						<td>{{$dn->no}}</td>
					</tr>
					<tr>
						<td>Date</td>
						<td>:</td>
						<td>{{$date}}</td>
					</tr>
					<tr>
						<td>PO Number</td>
						<td>:</td>
						<td>{{$dn->po_number}}</td>
					</tr>
					<tr>
						<td>PO From</td>
						<td>:</td>
						<td>{{$dn->po_from}}</td>
					</tr>
					<tr>
						<td>PO Date</td>
						<td>:</td>
						<td>{{$dn->po_date}}</td>
					</tr>
				</table>
			</td>
			<td width="40%">
				<table style="border: none">
					<tr>
						<td>Req Arrival</td>
						<td>:</td>
						<td>{{$dn->arrival_date}}</td>
					</tr>
					<tr>
						<td>Consignee</td>
						<td>:</td>
						<td>{{$dn->consignee}}</td>
					</tr>
					<tr>
						<td>Delivery To</td>
						<td>:</td>
						<td>{{$dn->delivery_to}}</td>
					</tr>
					<tr>
						<td>Address</td>
						<td>:</td>
						<td>{{$dn->address}}</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<br />

	<!-- Product Details Table -->
	<table width="100%" border="1" cellspacing="0" cellpadding="5" style="font-size:14px">
		<tr>
			<th>No</th>
			<th>QUANTITY</th>
			<th>UNITS</th>
			<th>DESCRIPTION</th>
			<th>NO SEGEL</th>
		</tr>
		<tr>
			<td rowspan="2" style="text-align: center">1</td>
			<td rowspan="2" style="text-align: center">{{$dn->quantity}}</td>
			<td rowspan="2" style="text-align: center">{{$dn->units}}</td>
			<td rowspan="2" style="text-align: center">{{$dn->description}}</td>
			<td>
				<table style="border:none">
					<tr>
						<td style="border: none; width:40%">Atas</td>
						<td style="border: none">:</td>
						<td style="border: none">{{$dn->segel_atas}}</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table style="border: none">
					<tr>
						<td style="border: none; width:40%">Bawah</td>
						<td style="border: none">:</td>
						<td style="border: none">{{$dn->segel_bawah}}</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<br />

	<!-- Notes Table -->
	<table width="100%" cellspacing="0" cellpadding="5" style="font-size:14px">
		<tr>
			<td>
				<strong>NOTE:</strong><br />
				1. Penerima di site <strong>Site PIC</strong> WAJIB mengisi tabel catatan pembongkaran<br />
				2. Transportir <strong>WAJIB</strong> mengisi tabel catatan pengiriman<br />
				3. Kran Tangki pengisian dan Pengeluaran <strong>WAJIB TERSEGEL</strong> dengan baik<br />
				4. PIC site dan driver <strong>WAJIB</strong> mengambil sampel solar dan kedua belah pihak <strong>WAJIB MENYEGEL</strong> <br />
				dengan baik dengan segel yang diberikan oleh PT Mina Marret Trans Energi Indonesia (dalam sosialisasi)

			</td>
		</tr>
	</table>

	<br />

	<!-- Shipping and Unloading Notes Table -->
	<table style="width:100%">
		<tr>
			<td style="width:50%">
				<table class="shipping" style="font-size:14px">
					<tr>
						<td style="text-align:center">
							<strong>Catatan Pengiriman</strong>
						</td>
					</tr>
					<tr>
						<td style="padding-left: 10px">
							<table style="border: none">
								<tr>
									<td style="border: none">No. LO</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->lo}}</td>
								</tr>
								<tr>
									<td style="border: none">No. SO</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->so}}</td>
								</tr>
								<tr>
									<td style="border: none">No. Polisi</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->nopol}}</td>
								</tr>
								<tr>
									<td style="border: none">Nama Driver</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->driver_name}}</td>
								</tr>
								<tr>
									<td style="border: none">Transportir</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->transportir}}</td>
								</tr>
								<tr>
									<td style="border: none">Tinggi Terra</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->terra ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none">Berat Jenis</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->berat_jenis ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none">Temperature</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->temperature ?? ''}}</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
			<td style="width:50%">
				<table class="shipping" style="font-size:14px">
					<tr>
						<td style="text-align:center">
							<strong>Catatan Pembongkaran</strong>
						</td>
					</tr>
					<tr>
						<td style="padding-left: 10px">
							<table style="border: none">
								<tr>
									<td style="border: none">Tanggal</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->tgl_bongkar ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none">Jam Mulai</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->jam_mulai ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none">Jam Akhir</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->jam_akhir ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none">Meter awal</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->meter_awal ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none">Meter Akhir</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->meter_akhir ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none">Tinggi Sounding</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->tinggi_sounding ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none">Berat Jenis & Suhu</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->jenis_suhu ?? ''}}</td>
								</tr>
								<tr>
									<td style="border: none"><strong>Volume yang diterima</strong></td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->volume_diterima ?? ''}}</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>



	<br />

	<!-- Signatures Table -->
	<table width="100%" style="font-size:14px">
		<tr>
			<td style="width:50%">
				<table class="signature">
					<tr>
						<td colspan="2" style="text-align: center; font-weight:bold">PT MINA MARRET TRANS ENERGI INDONESIA</td>
					</tr>
					<tr>
						<td style="text-align: center;">
							<img src="{{ $storage_url }}/logo/cap_ttd_mmtei.png" style="width: 160px; height: auto;" />
						</td>
						<td>
							<br />
							<br /><br /><br /><br /><br />
						</td>
					</tr>
					<tr>
						<td style="text-align: center;"><strong>M. Ramadan</strong></td>
						<td style="text-align: center;"><strong>{{$dn->driver_name}}</strong></td>
					</tr>
					<tr>
						<td style="text-align: center; font-weight:bold">Logistics</td>
						<td style="text-align: center; font-weight:bold">Driver</td>
					</tr>
				</table>
			</td>
			<td style="width:50%">
				<table class="signature">
					<tr>
						<td style="text-align: center; font-weight:bold">Diterima Oleh:</td>
					</tr>
					<tr>
						<td style="text-align: center;"">
                                BBM tersebut telah diperiksa dan diserah terimakan dengan
                                Kualitas Baik dan Cukup
								<br />
								<br /><br /><br /><br />
							</td>
						</tr>
						<tr>
							<td>Nama: .................................</td>
						</tr>
						<tr>
							<td style=" text-align: center; font-weight:bold">PIC Site</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<br />

	<!-- Remarks Table -->
	<table width="100%" cellspacing="0" cellpadding="5" style="font-size:14px">
		<tr>
			<td>
				<strong>Remarks:</strong> Harap di print 4 rangkap (2 rangkap untuk
				MMTEI, 1 rangkap untuk customer, 1 rangkap untuk transportir)
			</td>
		</tr>
	</table>
</body>

</html>
