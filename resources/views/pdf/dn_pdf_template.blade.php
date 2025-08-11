<!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>PT MINA MARRET LINTAS NUSANTARA - Delivery Note</title>
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
	<img style="width:100%; position:absolute; top: 20px; left: 10px !important; width: 13%; height:8%"
		src="{{ public_path('static/images/logo/logo.png') }}"
		alt="PT MINA MARRET LINTAS NUSANTARA Logo" />
	<table width="100%">
		<tr>
			<td align="center" colspan="2">
				<strong>PT MINA MARRET {{$dn->sph_type == "MMLN" ? "LINTAS NUSANTARA" : "TRANS ENERGI INDONESIA" }}</strong>
				<p>Berdasarkan Surat PT Pertamina Patra Niaga No. 16849/PRD0000000/2022-53 Tanggal 22 Desember 2022</p>
				<p>Jenis Komoditi / Produk: Solar HSD B35 MFO, Pertamax, Pertamina Turbo dan Dexlite</p>
				<p>World Capital Tower Lt. 05 Unit H, Jl. Dr. Ide Anak Agung Gde Agung Kuningan Jakarta Selatan, DKI Jakarta 12950</p>
				<p>Gagah Putera Satria Building, Jl. KP. Tendean No. 158 Banjarmasin, Kalimantan Selatan 70231</p>
				<p>Email: info@mmln.com | Telp: 0811 - 8888 - 2221</p>
			</td>
		</tr>
	</table>
	<hr>
	<table width="100%">
		<tr>
			<td align="center" colspan="2">
				<strong>DELIVERY NOTE <br /><span style="font-size: 12px;">BBM Non Subsidi</span></strong>
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
						<td>PO Number</td>
						<td>:</td>
						<td>{{$dn->po_number}}</td>
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
						<td style="border: none">{{$dn->no_segel_atas}}</td>
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
						<td style="border: none">{{$dn->no_segel_bawah}}</td>
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
				1. Penerima di site WAJIB mengisi tabel catatan pembongkaran<br />
				2. Transportir WAJIB mengisi tabel catatan pengiriman<br />
				3. Kelengkapan pengisian dan Pengeluaran WAJIB TERSEGEL dengan baik<br />
				<!-- 4. Pastikan anda mendapatkan SLIP LO Pertamina -->
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
									<td style="border: none">{{$dn->no_so}}</td>
								</tr>
								<tr>
									<td style="border: none">No. SO</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->no_lo}}</td>
								</tr>
								<tr>
									<td style="border: none">No. Polisi</td>
									<td style="border: none">:</td>
									<td style="border: none">{{$dn->no_police}}</td>
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
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Berat Jenis</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Temperature</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
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
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Jam Mulai</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Jam Akhir</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Meter awal</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Meter Akhir</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Tinggi Sounding</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Berat Jenis & Suhu</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
								</tr>
								<tr>
									<td style="border: none">Volume yang diterima</td>
									<td style="border: none">:</td>
									<td style="border: none"></td>
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
						<td colspan="2" style="text-align: center; font-weight:bold">PT MINA MARRET LINTAS NUSANTARA</td>
					</tr>
					<tr>
						<td>
							<br />
							<br /><br /><br /><br /><br />
						</td>
						<td>
							<br />
							<br /><br /><br /><br /><br />
						</td>
					</tr>
					<tr>
						<td>.</td>
						<td>.</td>
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
				MMLN, 1 rangkap untuk customer, 1 rangkap untuk transportir)
			</td>
		</tr>
	</table>
</body>

</html>
