# Reporting API – Endpoint, Payload & Response

## Base URL
`/api` (prefix sesuai aplikasi, misal `http://sph-api.lokal.test/api`)

---

## 1. Request Generate Report (antrian/queue)

**Endpoint:** `POST /api/reporting/request`

**Deskripsi:** Menambah request export report ke antrian. File Excel akan digenerate di background (queue) dan disimpan di BytePlus storage. Setelah selesai, status berubah menjadi `ready` dan bisa didownload dari list "Download Export".

### Payload (JSON)

| Field         | Type   | Wajib | Keterangan |
|---------------|--------|-------|------------|
| `report_type` | string | Ya    | `ar` \| `ap` \| `logistik` |
| `date_from`   | string | Untuk AR: Ya | Format `Y-m-d` (wajib jika `report_type` = `ar`) |
| `date_to`     | string | Untuk AR: Ya | Format `Y-m-d`, harus >= `date_from` (wajib jika `report_type` = `ar`) |
| `ap_sub_type` | string | Tidak | Hanya untuk `report_type` = `ap`: `all` \| `supplier` \| `transportir`. Default `all` |

**Contoh payload AR (dengan date range):**
```json
{
  "report_type": "ar",
  "date_from": "2026-01-01",
  "date_to": "2026-12-31"
}
```

**Contoh payload AP – ALL:**
```json
{
  "report_type": "ap",
  "date_from": "2026-01-01",
  "date_to": "2026-12-31",
  "ap_sub_type": "all"
}
```

**Contoh payload AP – Supplier:**
```json
{
  "report_type": "ap",
  "date_from": "2026-01-01",
  "date_to": "2026-12-31",
  "ap_sub_type": "supplier"
}
```

**Contoh payload AP – Transportir:**
```json
{
  "report_type": "ap",
  "date_from": "2026-01-01",
  "date_to": "2026-12-31",
  "ap_sub_type": "transportir"
}
```

**Contoh payload Logistik (tanpa date wajib; bisa dikosongkan atau diisi):**
```json
{
  "report_type": "logistik",
  "date_from": "2026-01-01",
  "date_to": "2026-12-31"
}
```

### Response (201 Created)

```json
{
  "message": "Report request berhasil ditambahkan ke antrian.",
  "data": {
    "id": 1,
    "report_type": "ar",
    "ap_sub_type": null,
    "date_from": "2026-01-01",
    "date_to": "2026-12-31",
    "status": "pending",
    "filename": null,
    "created_at": "2026-03-12T10:00:00.000000Z",
    "updated_at": "2026-03-12T10:00:00.000000Z"
  }
}
```

---

## 2. List Export (halaman Download Export)

**Endpoint:** `GET /api/reporting/exports`

**Deskripsi:** Mengambil daftar request export. Dipakai di halaman "Download Export" dengan tombol refresh untuk cek status. Jika `status` = `ready`, bisa pakai `download_url` untuk download.

### Query (opsional)

| Parameter   | Type | Default | Keterangan |
|------------|------|---------|------------|
| `per_page` | int  | 15      | Jumlah item per halaman |

### Response (200 OK)

```json
{
  "message": "OK",
  "data": [
    {
      "id": 1,
      "report_type": "ar",
      "ap_sub_type": null,
      "date_from": "2026-01-01",
      "date_to": "2026-12-31",
      "status": "ready",
      "filename": "report_ar_2026-01-01_2026-12-31_1.xlsx",
      "created_at": "2026-03-12T10:00:00.000000Z",
      "updated_at": "2026-03-12T10:05:00.000000Z",
      "download_url": "https://..."
    },
    {
      "id": 2,
      "report_type": "ap",
      "ap_sub_type": "supplier",
      "date_from": "2026-01-01",
      "date_to": "2026-12-31",
      "status": "processing",
      "filename": null,
      "created_at": "2026-03-12T10:06:00.000000Z",
      "updated_at": "2026-03-12T10:06:00.000000Z"
    },
    {
      "id": 3,
      "report_type": "ap",
      "ap_sub_type": "all",
      "date_from": null,
      "date_to": null,
      "status": "failed",
      "filename": null,
      "created_at": "2026-03-12T10:07:00.000000Z",
      "updated_at": "2026-03-12T10:07:30.000000Z",
      "error": "Pesan error dari sistem"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 3
  }
}
```

**Status:** `pending` | `processing` | `ready` | `failed`  
- `ready`: ada field `download_url` (presigned BytePlus), file siap didownload.  
- `failed`: ada field `error` berisi pesan error.

---

## 3. Download Export

**Endpoint:** `GET /api/reporting/exports/{id}/download`

**Deskripsi:**  
- Jika client mengirim header `Accept: application/json`: response JSON berisi `download_url` dan `filename`.  
- Jika tidak (browser/link langsung): response **redirect 302** ke URL download (BytePlus presigned). File Excel langsung ter-download.

Hanya bisa dipanggil untuk record dengan `status` = `ready`. File disimpan di BytePlus storage.

### Response (200 OK) – JSON

```json
{
  "message": "OK",
  "download_url": "https://...",
  "filename": "report_ar_2026-01-01_2026-12-31_1.xlsx"
}
```

### Response (302 Redirect)

Redirect ke `download_url` (presigned URL BytePlus, masa berlaku singkat).

### Error

| HTTP | Kondisi |
|------|--------|
| 400 | Status bukan `ready` |
| 403 | Tidak punya akses ke report ini |
| 404 | Report tidak ditemukan atau file tidak ada di storage |

---

## Ringkasan Endpoint

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST   | `/api/reporting/request`       | Request generate report (queue) |
| GET    | `/api/reporting/exports`       | List request export (Download Export + refresh status) |
| GET    | `/api/reporting/exports/{id}/download` | Download file (redirect atau JSON URL) |

---

## Flow FE

1. **Dropdown pilihan:** AR, AP, Logistik.
2. **Jika AR:** tampilkan datepicker range → kirim `report_type`, `date_from`, `date_to` ke `POST /api/reporting/request`.
3. **Jika AP:** tampilkan dropdown ALL / Supplier / Transportir → kirim `report_type`, `ap_sub_type`, dan opsional `date_from`/`date_to` ke `POST /api/reporting/request`.
4. **Menu Download Export:** panggil `GET /api/reporting/exports` untuk list; tombol **Refresh** = panggil lagi endpoint yang sama untuk cek status.
5. **Jika status `ready`:** tampilkan tombol Download; arahkan ke `GET /api/reporting/exports/{id}/download` (redirect) atau pakai `download_url` dari list/detail.

File disimpan di BytePlus storage; tidak disimpan di lokal server.
