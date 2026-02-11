# Perubahan API untuk FE – Async Generate PDF SPH

Generate PDF SPH sekarang berjalan **async** (di background). Response API tetap cepat; PDF digenerate di antrian dan bisa dipantau lewat endpoint monitoring.

---

## 1. Endpoint yang berubah (response saja)

### POST /api/sph/store
- **Payload:** Tidak berubah.
- **Response (201):**  
  - Sebelum: `{ "message": "SPH berhasil disimpan!" }`  
  - Sekarang: `{ "message": "SPH berhasil disimpan! PDF akan digenerate di background." }`
- **Akibat untuk FE:** Jangan harap `file_sph` / `temp_sph` terisi langsung setelah create. Gunakan **GET /api/sph/pdf-jobs** (filter `sph_id` atau `user_id`) untuk cek status dan dapat `pdf_url` setelah `status === 'success'`.

### POST /api/sph/update (updateSph)
- **Payload:** Tidak berubah.
- **Response (200):**  
  - Sebelum: `{ "message": "SPH berhasil diupdate!" }`  
  - Sekarang: `{ "message": "SPH berhasil diupdate! PDF akan digenerate di background." }`
- **Akibat untuk FE:** Sama seperti store; PDF dan link di-background. Pantau lewat **GET /api/sph/pdf-jobs**.

### POST /api/sph/store-details (SphStoreDetails)
- **Payload:** Tidak berubah.
- **Response (201):**  
  - Sebelum: `{ "message": "SPH dan details berhasil disimpan!", "sph_id": 123 }`  
  - Sekarang: `{ "message": "SPH dan details berhasil disimpan! PDF akan digenerate di background.", "sph_id": 123 }`
- **Akibat untuk FE:** Sama; gunakan monitoring untuk tahu kapan PDF selesai.

### POST /api/sph/{id}/approval (ApproveSph – final approval)
- **Payload:** Tidak berubah.
- **Response:** Tidak berubah (success/error sama).
- **Perilaku:** Setelah final approve, PDF digenerate di background. `file_sph` di SPH akan terisi setelah job selesai. FE bisa polling **GET /api/sph/pdf-jobs?sph_id={id}** atau **GET /api/sph/list** (kolom `file_sph`) untuk tahu kapan PDF ready.

---

## 2. Endpoint yang berubah (response + perilaku)

### POST /api/sph/{id}/recreate-pdf (recreateSph)
- **Payload:** Tidak berubah (auth + `id` di path).
- **Response (200) – BERUBAH:**  
  - Sebelum (sync):  
    `{ "success": true, "message": "PDF SPH berhasil digenerate ulang.", "pdf_url": "https://..." }`  
  - Sekarang (async):  
    `{ "success": true, "message": "PDF SPH sedang digenerate di background. Cek status di GET /api/sph/pdf-jobs.", "sph_id": 123, "pdf_job_id": 456 }`
- **Akibat untuk FE:**  
  - Jangan lagi pakai response `pdf_url` dari recreate-pdf.  
  - Simpan `pdf_job_id` (opsional) lalu pantau **GET /api/sph/pdf-jobs** (filter by `sph_id` atau by `pdf_job_id` lewat list) sampai `status === 'success'` dan ambil `pdf_url` dari item tersebut.  
  - Atau polling **GET /api/sph/list** / detail SPH untuk kolom `file_sph` jika backend mengisi itu setelah job sukses.

---

## 3. Endpoint baru untuk FE

### GET /api/sph/pdf-jobs (monitoring)
- **Auth:** Sama seperti API lain (token/client).
- **Query (semua optional):**
  - `status` – filter: `queued` | `processing` | `success` | `failed`
  - `sph_id` – filter by SPH id
  - `user_id` – filter by user yang trigger (triggered_by_user_id)
  - `per_page` – default 15, max 100
  - `page` – untuk pagination
- **Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sph_id": 123,
      "status": "success",
      "attempt": 1,
      "update_file_sph": true,
      "temp_sph_action": "update",
      "pdf_url": "https://... (BytePlus TOS presigned URL)",
      "error": null,
      "triggered_by_user_id": 5,
      "finished_at": "2026-02-11 12:00:00",
      "created_at": "2026-02-11 11:59:00",
      "updated_at": "2026-02-11 12:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```
- **Kegunaan untuk FE:**  
  - Tampilkan antrian (queued), sedang proses (processing), sukses (success + `pdf_url`), gagal (failed + `error`).  
  - Filter by `sph_id` untuk satu SPH atau by `user_id` untuk notifikasi “milik user ini”.

---

## 4. Ringkas untuk FE

| Aksi FE | Sebelum | Sekarang |
|--------|---------|----------|
| Create/Update SPH, Store details | Response langsung, bisa anggap PDF ready | Response langsung; PDF di background. Cek **GET /api/sph/pdf-jobs** (atau list SPH) untuk status & `pdf_url`. |
| Recreate PDF | Response berisi `pdf_url` | Response berisi `sph_id` + `pdf_job_id`; `pdf_url` dari **GET /api/sph/pdf-jobs** atau dari data SPH setelah job success. |
| Tampilkan status generate PDF | Tidak ada | Pakai **GET /api/sph/pdf-jobs** (filter `status`, `sph_id`, `user_id`). |

**Payload request** untuk store, update, store-details, approval, dan recreate-pdf **tidak berubah**. Hanya response dan cara dapat `pdf_url` / status yang disesuaikan dengan async + monitoring.

---

## 5. Backend: menjalankan queue worker

Agar job PDF benar-benar diproses, jalankan worker (di server atau supervisor):

```bash
php artisan queue:work
```

Atau dengan driver yang dipakai (mis. database):

```bash
php artisan queue:work --queue=default
```

Jika queue tidak dijalankan, job akan menumpuk di tabel `jobs` dan status di `sph_pdf_jobs` akan tetap `queued`.
