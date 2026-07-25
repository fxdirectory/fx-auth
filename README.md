# FX Auth Microservice

Slim PHP microservice untuk autentikasi dengan login, logout, refresh token, dan role.

## Struktur Folder

- `function/` - logic aplikasi seperti controller
- `utils/` - helper dan utilitas reusable
- `middle/` - middleware aplikasi
- `conf/` - konfigurasi aplikasi
- `db/` - migrations dan seeds database
- `public/` - entry point Apache
- `routes/` - definisi route Slim
- `vendor/` - dependency Composer

## Instalasi

```bash
composer install
cp .env.example .env
```

## Jalankan

```bash
composer start
```

## Nginx

Project ini bisa dijalankan lewat Nginx dengan root mengarah ke folder `public/`.
Contoh konfigurasi tersedia di `nginx/fx-auth.conf`.

Jika memakai konfigurasi dedicated domain seperti `fx-auth.test`, gunakan:

```env
APP_BASE_PATH=
APP_URL=http://fx-auth.test
```

Pastikan PHP-FPM/FastCGI berjalan di `127.0.0.1:9000`, atau sesuaikan nilai `fastcgi_pass` di konfigurasi Nginx.

## Migrasi Database

Rekomendasi migration tool sederhana: `robmorgan/phinx`.

1. Install dependency:

```bash
composer install
```

2. Jalankan migrasi:

```bash
composer migrate
```

3. Jika ingin membuat migration baru:

```bash
vendor/bin/phinx create CreateAuthTables
```
