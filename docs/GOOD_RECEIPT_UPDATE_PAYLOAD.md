# POST /api/good-receipts/{po_id}/update — Payload & Response

## Alur
1. Data **good_receipt** saat ini (per `po_id`) di-**copy** ke table **gr_revisi_log** (satu baris log revisi).
2. Setelah itu **update** good_receipt (dan detail) sesuai payload.
3. Jika FE **tidak mengirim file** (po_file/file), kolom **po_file** di good_receipt **tidak diubah** (tetap nilai lama).

---

## Payload (body)

**Content-Type:** `application/json` atau **multipart/form-data** (jika ada upload file).

| Field | Type | Required | Keterangan |
|-------|------|----------|------------|
| `po_no` | string | ✅ | Min 3 karakter |
| `no_seq` | string | ❌ | Nullable |
| `wilayah` | string | ✅ | |
| `source` | string | ✅ | Contoh: `"IASE"` |
| `sub_total` | number | ✅ | |
| `ppn` | number | ✅ | |
| `pbbkb` | number | ✅ | |
| `pph` | number | ✅ | |
| `total` | number | ✅ | |
| `terbilang` | string | ✅ | Terbilang total |
| `status` | integer | ✅ | Harus `1` |
| `items` | array | ✅ | Min 1 item |
| `items[].nama_item` | string | ✅ | |
| `items[].qty` | number | ✅ | Min 1 |
| `items[].per_item` | number | ✅ | Min 0 |
| `items[].total_harga` | number | ✅ | Min 0 |
| `file` | file (PDF) | ❌ | **Optional.** Jika tidak dikirim, po_file tidak di-update. Max 2MB. |

---

## Contoh payload (tanpa upload file)

**Request:** `POST /api/good-receipts/123/update`  
**Body (JSON):**

```json
{
  "po_no": "PO-2026-001",
  "no_seq": "020",
  "wilayah": "02",
  "source": "IASE",
  "sub_total": 140000,
  "ppn": 15400,
  "pbbkb": 10500,
  "pph": 0,
  "total": 165900,
  "terbilang": "Seratus enam puluh lima ribu sembilan ratus rupiah",
  "status": 1,
  "items": [
    {
      "nama_item": "HSD Solar",
      "qty": 10,
      "per_item": 14000,
      "total_harga": 140000
    },
    {
      "nama_item": "Ongkos Angkut",
      "qty": 10,
      "per_item": 900,
      "total_harga": 9000
    }
  ]
}
```

Tidak ada field `file` → **po_file** di good_receipt tetap seperti sebelumnya.

---

## Contoh payload (dengan upload file PO)

**Request:** `POST /api/good-receipts/123/update`  
**Content-Type:** `multipart/form-data`

- Semua field di atas dikirim sebagai form field (atau `items` sebagai string JSON).
- Tambah field **file**: file PDF (max 2MB).

Contoh (form-data):

```
po_no: PO-2026-001
no_seq: 020
wilayah: 02
source: IASE
sub_total: 140000
ppn: 15400
pbbkb: 10500
pph: 0
total: 165900
terbilang: Seratus enam puluh lima ribu sembilan ratus rupiah
status: 1
items: [{"nama_item":"HSD Solar","qty":10,"per_item":14000,"total_harga":140000},{"nama_item":"Ongkos Angkut","qty":10,"per_item":900,"total_harga":9000}]
file: [file PDF]
```

Jika **file** dikirim → **po_file** di good_receipt di-update ke path file yang baru.

---

## Response sukses (200)

```json
{
  "code": 200,
  "message": "Good Receipt berhasil disimpan."
}
```

## Response error (500)

```json
{
  "code": 500,
  "message": "Gagal menyimpan Good Receipt!",
  "error": "Detail exception..."
}
```
