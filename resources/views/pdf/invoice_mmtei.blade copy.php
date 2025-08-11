<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - PT MINA MARRET TRANS ENERGI INDONESIA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.2.7/dist/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .invoice-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2.5rem;
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        @media print {
            .invoice-container {
                box-shadow: none;
                margin: 0;
            }
            body {
                background-color: #ffffff;
            }
        }
        .invoice-table th, .invoice-table td {
            border: 2px solid #000;
            padding: 8px 16px;
        }
        .invoice-table {
            border-collapse: collapse;
        }

</style>
</head>
<body>
    <div class="invoice-container">
        <!-- HEADER SECTION - INVOICE text and company info with logo -->
        <header class="flex justify-between items-center mb-4">
            <div class="text-2xl font-bold flex-shrink-0 mr-4">
                INVOICE
            </div>
            <div class="flex-grow text-center flex-col items-center">
                <h2 class="text-lg font-bold">PT MINA MARRET TRANS ENERGI INDONESIA</h2>
                <p class="text-xs font-semibold text-gray-600 mt-1">AGEN BBM INDUSTRI</p>
                <p class="text-[10px] text-gray-500 mt-2">
                    World Capital Tower LT. 05 Unit 01, Jl. DR. Ide Anak Agung Gde Lot D. Lingkar Mega Kuningan Kota Administrasi Jakarta Selatan DKI Jakarta 12950<br>
                    Gagah Putera Satria Building, Jl. KP. Tendean No. 158 Banjarmasin, Kalimantan Selatan 70231<br>
                    Telp: +62-811-888-2221
                </p>
            </div>
            <div class="flex-shrink-0 ml-4">
                <img src="https://is3.cloudhost.id/bensinkustorage/logo/mina-marret-logo.png"  alt="Company Logo" class="w-12 h-12 sm:w-16 sm:h-16 object-contain rounded-lg">
            </div>
        </header>

        <!-- Thick gray line -->
        <hr class="border-t-8 border-gray-800 -mt-2 mb-4">

                 <!-- INVOICE DETAILS - NO, DATE, PO, TERMS -->
         <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm font-semibold mt-4">
             <div>
                 <p class="leading-none">INVOICE NO:</p>
                 <p class="font-normal leading-none">{{ $invoice->invoice_no ?? 'N/A' }}</p>
             </div>
             <div>
                 <p class="leading-none">INVOICE DATE:</p>
                 <p class="font-normal leading-none">{{ $invoice->invoice_date ?? 'N/A' }}</p>
             </div>
             <div>
                 <p class="leading-none">PO NO:</p>
                 <p class="font-normal leading-none">{{ $invoice->po_no ?? 'N/A' }}</p>
             </div>
             <div>
                 <p class="leading-none">TERMS:</p>
                 <p class="font-normal leading-none">{{ $invoice->terms ?? 'N/A' }}</p>
             </div>
         </div>

        <!-- Thin gray line under invoice details -->
        <hr class="border-t border-gray-400 my-4">

                 <!-- BILL TO and SHIP TO sections -->
         <div class="flex flex-col sm:flex-row justify-between mb-8 text-sm">
             <div class="mb-4 sm:mb-0">
                 <p class="font-bold">BILL TO:</p>
                 <address class="not-italic">
                     <strong>{{ $invoice->bill_to ?? 'N/A' }}</strong><br>
                     <span class="text-xs">{{ $invoice->bill_to_address ?? 'N/A' }}</span>
                 </address>
             </div>
             <div class="text-left sm:text-right">
                 <p class="font-bold">SHIP TO:</p>
                 <address class="not-italic">
                     <strong>{{ $invoice->ship_to ?? 'N/A' }}</strong>
                 </address>
             </div>
         </div>

                 <!-- SENT DATE, F.O.B POINT, SENT VIA -->
         <div class="overflow-x-auto mb-8">
             <table class="min-w-full bg-white invoice-table border-2 border-black text-xs">
   <thead>
     <tr>
       <td class="py-2 px-4 text-left font-bold" style="border-right: none;">SENT DATE: {{ $invoice->sent_date ?? 'N/A' }}</td>
       <th class="py-2 px-4 text-left font-bold" style="border-left: none; border-right: none;">F.O.B POINT: {{ $invoice->fob ?? 'N/A' }}</th>
       <th class="py-2 px-4 text-left font-bold" style="border-left: none;">SENT VIA: {{ $invoice->sent_via ?? 'N/A' }}</th>
     </tr>
   </thead>
 </table>
             <div style="height:8px; line-height:0; font-size:0;"></div>
             <div class="overflow-x-auto mb-8">

         <!-- Invoice items table -->

             <table class="min-w-full bg-white rounded-lg invoice-table">
                 <thead>
                     <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                         <th class="py-3 px-6 text-left font-bold" width=20%>Kode Barang</th>
                         <th class="py-3 px-6 text-left font-bold" width=50%>DESCRIPTION</th>
                         <th class="py-3 px-6 text-left font-bold" width=5%>QUANTITY</th>
                         <th class="py-3 px-6 text-left font-bold" width=5%>UNIT PRICE</th>
                         <th class="py-3 px-6 text-left font-bold" width=5%>Diskon</th>
                         <th class="py-3 px-6 text-left font-bold" width=5%>AMOUNT</th>
                     </tr>
                 </thead>
                 <tbody class="text-gray-600 text-sm font-light">
                     @if(isset($details) && count($details) > 0)
                         @foreach($details as $index => $detail)
                             <tr>
                                 <td class="py-3 px-6 text-left whitespace-nowrap">{{ $index + 1 }}</td>
                                 <td class="py-3 px-6 text-left">{{ $detail->nama_item ?? 'N/A' }}</td>
                                 <td class="py-3 px-6 text-left">{{ number_format($detail->qty ?? 0, 0, ',', '.') }}</td>
                                 <td class="py-3 px-6 text-left">{{ number_format($detail->harga ?? 0, 0, ',', '.') }}</td>
                                 <td class="py-3 px-6 text-left">0</td>
                                 <td class="py-3 px-6 text-left">{{ number_format($detail->total ?? 0, 0, ',', '.') }}</td>
                             </tr>
                         @endforeach
                     @else
                         <tr>
                             <td class="py-3 px-6 text-left whitespace-nowrap">1</td>
                             <td class="py-3 px-6 text-left">No items available</td>
                             <td class="py-3 px-6 text-left">0</td>
                             <td class="py-3 px-6 text-left">0</td>
                             <td class="py-3 px-6 text-left">0</td>
                             <td class="py-3 px-6 text-left">0</td>
                         </tr>
                     @endif
                 </tbody>
             </table>
         </div>
         </div>

                 <div class="-mt-8 flex flex-col sm:flex-row justify-between items-start text-sm">
             <div class="mb-8 sm:mb-0">
                 <p class="font-bold">REKENING PEMBAYARAN:</p>
                 <p>BCA CABANG WTC SUDIRMAN,</p>
                 <p>JAKARTA PUSAT AN.PT MINA MARRET</p>
                 <p>TRANS ENERGI INDONESIA</p>
                 <p>ACCT NO. 5455-678991</p>
             </div>
             <div>
                 <table class="w-full sm:w-64 float-right border-collapse border-2 border-black">
           <tbody>
             <tr>
               <td class="py-1 px-4 text-left border border-black">Sub Total</td>
               <td class="py-1 px-4 text-left border border-black" style="border-right: none;">Rp</td>
               <td class="py-1 px-4 text-right border border-black" style="border-left: none;">{{ number_format($invoice->sub_total ?? 0, 0, ',', '.') }}</td>
             </tr>
             <tr>
               <td class="py-1 px-4 text-left border border-black">Diskon</td>
               <td class="py-1 px-4 text-left border border-black" style="border-right: none;">Rp</td>
               <td class="py-1 px-4 text-right border border-black" style="border-left: none;">{{ number_format($invoice->diskon ?? 0, 0, ',', '.') }}</td>
             </tr>
             <tr>
               <td class="py-1 px-4 text-left border border-black">PPN (11%)</td>
               <td class="py-1 px-4 text-left border border-black" style="border-right: none;">Rp</td>
               <td class="py-1 px-4 text-right border border-black" style="border-left: none;">{{ number_format($invoice->ppn ?? 0, 0, ',', '.') }}</td>
             </tr>
             <tr>
               <td class="py-1 px-4 text-left border border-black">PBBKB</td>
               <td class="py-1 px-4 text-left border border-black" style="border-right: none;">Rp</td>
               <td class="py-1 px-4 text-right border border-black" style="border-left: none;">{{ number_format($invoice->pbbkb ?? 0, 0, ',', '.') }}</td>
             </tr>
             <tr>
               <td class="py-1 px-4 text-left border border-black">PPH 23</td>
               <td class="py-1 px-4 text-left border border-black" style="border-right: none;">Rp</td>
               <td class="py-1 px-4 text-right border border-black" style="border-left: none;">{{ number_format($invoice->pph ?? 0, 0, ',', '.') }}</td>
             </tr>
             <tr class="font-bold text-gray-800 bg-gray-100">
               <td class="py-2 px-4 text-left border-2 border-black">Total</td>
               <td class="py-2 px-4 text-left border-2 border-black" style="border-right: none;">Rp</td>
               <td class="py-2 px-4 text-right border-2 border-black" style="border-left: none;">{{ number_format($invoice->total ?? 0, 0, ',', '.') }}</td>
             </tr>
           </tbody>
         </table>
             </div>
         </div>

        <div class="clear-both pt-8 mt-8 border-t border-gray-200 text-xs text-gray-500">
            <table>
            <tr>
                <td>Terbilang</td>
                <td>:</td>
                <td>{{ $invoice->terbilang ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Keterangan</td>
                <td>:</td>
                <td>BUKTI PEMBAYARAN HARAP DIKIRIMKAN KE EMAIL: FINANCE@MINAMARRETENERGI.COM, SERTA MENCANTUMKAN NOMOR PO SEBAGAI BUKTI PEMBAYARAN YANG SAH.</td>
            </tr>
        </table>
          <div class="mt-8 flex justify-end">
              <div class="text-center">
                <!-- Nama Perusahaan (tidak digaris bawah) -->
                <p class="font-bold px-4">PT MINA MARRET TRANS ENERGI INDONESIA</p><br><br><br>
                <!-- Nama Orang (digaris bawah) -->
                <p class="font-bold inline-block border-b-2 border-black mt-2 px-4">
                  MINASARI MINGNA
                </p>
                <!-- Jabatan -->
                <p class="text-xs mt-1">DIREKTUR</p>
              </div>
            </div>
        </div>
    </div>
</body>
</html>
