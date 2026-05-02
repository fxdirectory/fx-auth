# FX Auth Microservice

Slim PHP microservice untuk autentikasi dengan login, logout, refresh token, dan role.

## Struktur Folder

- `app/` - kode aplikasi
- `public/` - entry point Apache
- `storage/` - logs dan cache
- `tests/` - unit dan feature test

## Instalasi

```bash
composer install
cp .env.example .env
```

## Jalankan

```bash
composer start
```

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
