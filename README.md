# Single-URL Local Media Player (Simple)

Ringkasan:
- Satu URL publik `player.php` yang selalu menampilkan *current* media.
 - Upload media lewat `upload.php` (publik, tanpa autentikasi), atau untuk per-channel upload gunakan `upload.php?channel=NAME`.
- Hanya media lokal disimpan di folder `uploads/`.

Cara pakai:
1. Letakkan folder ini di webroot server PHP Anda (mis. `public_html` atau `www`).
2. Pastikan PHP bisa menulis folder `uploads/` dan `data/` (server harus punya permission).
3. Gunakan `index.php` untuk semua operasi utama: membuat channel, meng-upload, dan mem-preview player tanpa mengganti URL. Buka `index.php` di browser.
	- `manage.php` tersedia untuk manajemen tambahan (opsional), dapat dibuka di browser jika perlu.
4. Buka `player.php` untuk akses langsung ke player (mis. `player.php/tvcr2`).

Konfigurasi:
- `config.php` berisi pengaturan seperti `MAX_FILE_SIZE` dan `ALLOWED_MIME`.

Keamanan & Catatan penting:
- Endpoint upload bersifat publik (TANPA AUTENTIKASI). Siapa saja yang menemukan `upload.php` dapat meng-overwrite media saat ini.
- Jangan gunakan ini pada situs publik tanpa menambahkan autentikasi atau proteksi lain.
- Batasi akses ke folder ini melalui mekanisme server (IP whitelist, htpasswd, dsb.) jika perlu.

Perbaikan yang direkomendasikan:
- Tambahkan autentikasi sederhana (kunci/admin) jika hanya admin yang seharusnya dapat mengganti media.
- Tambahkan validasi lebih ketat atau antivirus scanning untuk file upload jika digunakan di lingkungan tidak terpercaya.

Channel features:
 - Manage channels: `manage.php` (open access by default — use server-level protection if needed).
- Create channel: name + description.
- Channel player URL: `player.php/{channel}` e.g. `player.php/tvcr2`.
- Channel metadata stored in `data/channels.json`.

Using Bootstrap (npm):
- This project can include Bootstrap via `npm` to style the `index.php` and `manage.php` pages.
- To install & copy Bootstrap assets into the project (optional), run:

```bash
npm install
npm run build
```

This installs Bootstrap into `node_modules` and copies dist CSS/JS into `vendor/bootstrap/` via `scripts/copy-bootstrap.js`.

If you don't want to use npm, the pages include a CDN fallback for Bootstrap so they still look decent.
