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

## 3. Environment production

Pastikan di `.env` production:

- `QUEUE_CONNECTION=database` (atau `redis` jika pakai Redis)
- Tabel `jobs` ada (sudah dijalankan `php artisan queue:table` + `migrate`)

---

## Ringkasan

| Lingkungan   | Cara jalan |
|-------------|------------|
| Development | Manual: `php artisan queue:work` |
| Production  | Supervisor atau systemd agar worker jalan terus + restart otomatis |

Setelah config Supervisor/systemd, job PDF SPH (`GenerateSphPdfJob`) akan diproses di background tanpa perlu menjalankan `queue:work` manual.
