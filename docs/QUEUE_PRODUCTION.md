# Queue Worker di Production

Di development Anda bisa jalankan manual:
```bash
php artisan queue:work
```

Di **production** worker harus jalan terus dan restart otomatis jika crash. Beberapa opsi:

---

## 1. Supervisor (disarankan di VPS/VM)

1. **Install Supervisor** (jika belum):
   ```bash
   # Ubuntu/Debian
   sudo apt update && sudo apt install supervisor -y
   ```

2. **Buat config** dari contoh:
   ```bash
   sudo cp /var/www/sph-api/deploy/supervisor-laravel-worker.conf.example /etc/supervisor/conf.d/sph-api-worker.conf
   ```
   Edit jika path atau user berbeda (misalnya path app bukan `/var/www/sph-api`, user bukan `www-data`).

3. **Load & jalankan**:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start sph-api-worker:*
   ```

4. **Cek status**:
   ```bash
   sudo supervisorctl status sph-api-worker:*
   ```

5. **Setelah deploy/update code**, restart worker agar pakai code terbaru:
   ```bash
   sudo supervisorctl restart sph-api-worker:*
   ```

---

## 2. Systemd (alternatif Linux)

Buat file `/etc/systemd/system/sph-api-queue.service`:

```ini
[Unit]
Description=SPH API Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sph-api
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Lalu:
```bash
sudo systemctl daemon-reload
sudo systemctl enable sph-api-queue
sudo systemctl start sph-api-queue
sudo systemctl status sph-api-queue
```

Restart setelah deploy:
```bash
sudo systemctl restart sph-api-queue
```

---

## 3. Docker (queue worker di dalam container)

Kalau aplikasi jalan di Docker, **PHP hanya ada di dalam container**. Supervisor di host tidak bisa memanggil `php`; worker harus jalan **di dalam container**.

**Cara yang benar:** tambah satu **service** di Docker Compose yang hanya menjalankan `queue:work`. Docker yang menjaga proses (restart jika crash).

Contoh penambahan di `docker-compose.yml` (sesuaikan nama service dan path):

```yaml
  # Service aplikasi (web/API) — yang sudah ada
  # app:
  #   build: ./backend
  #   ...

  # Service worker queue — tambahkan ini
  sph-queue:
    build: ./backend
    # atau: image: your-registry/sph-api:latest
    command: php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
    restart: unless-stopped
    volumes:
      - ./backend:/var/www/html
    env_file: .env
    depends_on:
      - db
```

- Ganti `./backend` / `build` jika path atau Dockerfile Anda berbeda.
- Pastikan service ini pakai **env dan koneksi DB** yang sama dengan app (lewat `env_file` / `environment`).
- Setelah edit: `docker compose up -d --build` (atau `docker-compose up -d`). Service `sph-queue` akan jalan terus dan restart otomatis.

**Stop worker di host:** kalau tadi Anda pakai Supervisor di host untuk queue, bisa dinonaktifkan agar tidak konflik:

```bash
sudo supervisorctl stop sph-api-worker:*
# dan hapus/rename config di /etc/supervisor/conf.d/sph-api-worker.conf jika tidak dipakai
```

---

## 4. Environment production

Pastikan di `.env` production:

- `QUEUE_CONNECTION=database` (atau `redis` jika pakai Redis)
- Tabel `jobs` ada (sudah dijalankan `php artisan queue:table` + `migrate`)

---

## Ringkasan

| Lingkungan   | Cara jalan |
|-------------|------------|
| Development | Manual: `php artisan queue:work` |
| Production (VM/VPS) | Supervisor atau systemd di host |
| Production (Docker) | Service terpisah di Docker Compose yang menjalankan `queue:work` di dalam container |

Setelah config Supervisor/systemd, job PDF SPH (`GenerateSphPdfJob`) akan diproses di background tanpa perlu menjalankan `queue:work` manual.
