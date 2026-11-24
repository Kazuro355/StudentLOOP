#!/usr/bin/env bash
# Pasang StudentLOOP ke project Laravel 12 yang baru.
# Pemakaian:  ./install.sh /path/ke/project-laravel
set -euo pipefail

TARGET="${1:-}"
if [ -z "$TARGET" ] || [ ! -f "$TARGET/artisan" ]; then
  echo "Pemakaian: ./install.sh /path/ke/project-laravel   (folder harus berisi file 'artisan')" >&2
  exit 1
fi

HERE="$(cd "$(dirname "$0")" && pwd)"

for dir in app database routes public resources tests; do
  mkdir -p "$TARGET/$dir"
  cp -R "$HERE/$dir/." "$TARGET/$dir/"
done

# Migrasi bawaan Laravel membuat tabel users; migrasi kita menambah kolom setelahnya.
cd "$TARGET"

# Locale Indonesia untuk tanggal (translatedFormat) dan zona waktu Jakarta
if [ -f .env ]; then
  sed -i.bak 's/^APP_LOCALE=.*/APP_LOCALE=id/; s/^APP_NAME=.*/APP_NAME=StudentLOOP/' .env && rm -f .env.bak
  grep -q '^APP_TIMEZONE=' .env || echo 'APP_TIMEZONE=Asia/Jakarta' >> .env
fi
sed -i.bak "s/'timezone' => 'UTC'/'timezone' => env('APP_TIMEZONE', 'Asia\/Jakarta')/" config/app.php 2>/dev/null && rm -f config/app.php.bak || true

php artisan storage:link || true
php artisan migrate:fresh --seed

echo
echo "Selesai. Jalankan:  php artisan serve"
echo "Akun demo: andika@demo.test / marcel@demo.test / dafi@demo.test / daffa@demo.test / eric@demo.test (kata sandi: password123)"
