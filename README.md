# NiagaKita

Toko online PHP native untuk berbagai kategori produk. Fitur utama mencakup produk dan varian, stok, stock opname, diskon, checkout WhatsApp, pengiriman manual/RajaOngkir, pengaturan landing page, tema, dan installer web.

## Kebutuhan Server

- cPanel dengan Terminal dan Git.
- PHP 8.2 atau lebih baru.
- MySQL 5.7+ atau MariaDB setara.
- Apache dengan `mod_rewrite` dan `.htaccess` aktif.
- Composer tersedia di cPanel Terminal.
- Extension PHP: `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `gd` atau `imagick`.

## Deploy Dengan Git Clone

Panduan ini memakai mode satu folder. Semua source berada langsung di `public_html`, sehingga cocok untuk pengguna cPanel awam.

### 1. Buat Database

Di cPanel, buka **MySQL Databases**.

1. Buat database.
2. Buat user database dengan password kuat.
3. Tambahkan user ke database.
4. Beri hak **ALL PRIVILEGES**.

Nama database dan user biasanya memakai prefix username cPanel. Contoh:

```text
Database: akun_toko
User: akun_toko_user
```

Simpan detail ini untuk installer. Jangan gunakan MySQL user `root` di hosting.

### 2. Buka cPanel Terminal

Buka:

```text
cPanel -> Terminal
```

Masuk ke `public_html`:

```bash
cd ~/public_html
```

Pastikan folder kosong sebelum clone. Perintah berikut hanya untuk memeriksa isi:

```bash
ls -la
```

Jika ada file landing page default hosting seperti `index.html`, hapus melalui File Manager atau Terminal sebelum clone:

```bash
rm -f index.html
```

Jangan menjalankan `rm -rf` pada `public_html` jika sudah berisi website lain.

### 3. Clone Repository

Clone langsung ke folder saat ini:

```bash
git clone https://github.com/zenhosta/niagakita-toko-online.git .
```

Titik `.` di akhir wajib. Tanpanya Git akan membuat subfolder dan domain tidak menemukan `index.php`.

Pastikan file utama tersedia:

```bash
ls -la index.php .htaccess composer.json
```

Hasil yang benar:

```text
public_html/index.php
public_html/.htaccess
public_html/app/
public_html/config/
public_html/storage/
```

### 4. Install Dependency PHP

Masih di dalam `public_html`:

```bash
composer install --no-dev --optimize-autoloader
```

Jika command `composer` tidak ditemukan, coba:

```bash
php /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader
```

Atau hubungi support hosting untuk mengaktifkan Composer.

### 5. Atur Permission

Jalankan:

```bash
chmod 755 config storage storage/logs storage/uploads
chmod 755 storage/uploads/products storage/uploads/branding storage/uploads/homepage storage/uploads/footer
```

Jika installer atau upload gambar gagal menulis file, gunakan `775` hanya untuk folder berikut:

```bash
chmod 775 config storage storage/logs storage/uploads
chmod 775 storage/uploads/products storage/uploads/branding storage/uploads/homepage storage/uploads/footer
```

Jangan gunakan permission `777`.

### 6. Pilih PHP 8.2+

Di cPanel, buka **MultiPHP Manager** atau **Select PHP Version**.

Pilih PHP 8.2 atau lebih baru lalu aktifkan extension:

```text
pdo_mysql
fileinfo
mbstring
openssl
gd
```

Set juga konfigurasi produksi:

```text
display_errors = Off
log_errors = On
```

### 7. Jalankan Installer

Buka domain melalui HTTPS:

```text
https://domain-anda.com/install
```

Isi form:

```text
Kode lisensi: zenhosta
Host database: localhost
Port database: 3306
Nama database: nama database dari cPanel
User database: user database dari cPanel
Password database: password user database
```

Untuk cPanel, matikan pilihan **Buat database bila belum ada**. Database sudah dibuat dari menu MySQL Databases.

Installer akan:

1. Memeriksa kebutuhan PHP dan permission folder.
2. Membuat tabel aplikasi.
3. Menambahkan data dasar role, tema, dan pengaturan.
4. Membuat akun admin.
5. Membuat `config/database.local.php`.
6. Mengunci installer melalui `storage/installed.lock`.

Setelah sukses, masuk melalui:

```text
https://domain-anda.com/login
```

## HTTPS dan Domain

Aktifkan SSL dari:

```text
cPanel -> SSL/TLS Status -> Run AutoSSL
```

Setelah SSL aktif, gunakan domain HTTPS saja:

```text
https://domain-anda.com
```

## Update Dari GitHub

Sebelum update, backup database dan folder upload terlebih dahulu.

```bash
cd ~/public_html
git status
git pull origin main
composer install --no-dev --optimize-autoloader
```

File berikut tidak akan tertimpa oleh Git karena ada di `.gitignore`:

```text
config/database.local.php
storage/installed.lock
storage/logs/*.log
storage/uploads/
vendor/
```

Jangan menjalankan perintah berikut di produksi:

```bash
git reset --hard
git clean -fd
```

Perintah tersebut dapat menghapus file konfigurasi atau upload jika salah digunakan.

## Keamanan Deployment

File `.htaccess` root sudah memblokir akses browser ke folder berikut:

```text
app
bin
bootstrap
config
database
routes
storage
vendor
```

Setelah deploy, pastikan URL sensitif tidak dapat dibuka:

```text
https://domain-anda.com/config/database.local.php
https://domain-anda.com/database/schema.sql
https://domain-anda.com/storage/logs/app.log
https://domain-anda.com/vendor/autoload.php
```

Semua harus memberi `403` atau `404`, bukan isi file.

Route aplikasi yang wajib berjalan:

```text
https://domain-anda.com/
https://domain-anda.com/login
https://domain-anda.com/produk
https://domain-anda.com/media/{token}
```

## Troubleshooting

### Domain menampilkan daftar file

Periksa:

```bash
ls -la ~/public_html/index.php ~/public_html/.htaccess
```

`index.php` dan `.htaccess` harus berada langsung di `public_html`.

Di cPanel, buka **Indexes** lalu pilih:

```text
No Indexing
```

### `/login` atau `/produk` menghasilkan 404

Penyebab umum: `.htaccess` tidak terbaca atau `mod_rewrite` tidak aktif.

1. Aktifkan **Show Hidden Files** di File Manager.
2. Pastikan `.htaccess` ikut ter-clone.
3. Hubungi hosting dan minta `mod_rewrite` serta `AllowOverride All` aktif untuk domain.

### Installer gagal menyimpan konfigurasi

Periksa permission:

```bash
chmod 775 ~/public_html/config ~/public_html/storage
```

Lalu pastikan user database punya **ALL PRIVILEGES**.

### Gambar produk tidak tampil

Pastikan folder upload dapat ditulis:

```bash
chmod 775 ~/public_html/storage/uploads ~/public_html/storage/uploads/products
```

Gambar aplikasi dilayani lewat route tanpa ekstensi, misalnya:

```text
/media/ca9889b31fb4939997825e66
```

Jangan membuka path internal `storage/uploads/...` langsung dari browser.

### Composer tidak tersedia

Minta hosting mengaktifkan Composer atau upload folder `vendor/` dari hasil command lokal:

```bash
composer install --no-dev --optimize-autoloader
```

## Backup Rutin

Backup minimal mencakup:

```text
Database MySQL
config/database.local.php
storage/uploads/
```

Jangan menyimpan backup database di dalam `public_html`.
