# Deploy Steps

---

## LOCAL / DEV

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ & npm
- SQLite (built-in) **atau** MySQL/MariaDB
- (Opsional) Redis — jika tidak ada, gunakan fallback `file`

---

### Step-by-Step

```bash
# 1. Clone & masuk folder
git clone <repo-url> ai-project
cd ai-project

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies & build assets
npm install
npm run build

# 4. Buat file .env dari contoh
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Buat database (SQLite default)
touch database/database.sqlite

# 7. Jalankan migrasi + seeder
php artisan migrate --seed

# 8. Buat symlink storage
php artisan storage:link

# 9. Jalankan semua service sekaligus (server + queue worker + vite)
composer dev
```

---

### ENV yang Wajib Diisi (Local)

```env
APP_NAME=AI-Bot
APP_ENV=local
APP_KEY=                        # diisi otomatis oleh key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_LEVEL=debug

# Database — SQLite (default)
DB_CONNECTION=sqlite
# Jika pakai MySQL, uncomment baris di bawah:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ai_bot
# DB_USERNAME=root
# DB_PASSWORD=

# Queue
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=210
REDIS_QUEUE_RETRY_AFTER=210

# Cache — gunakan file jika Redis belum terinstall
CACHE_STORE=file
# Jika Redis sudah jalan, ganti ke:
# CACHE_STORE=redis
# REDIS_HOST=127.0.0.1
# REDIS_PORT=6379

# OpenAI
OPENAI_API_KEY=sk-...

# Telegram (isi jika ingin test webhook Telegram)
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=

# WhatsApp via WAHA (isi jika ingin test WhatsApp)
WAHA_BASE_URL=http://localhost:3000
WAHA_SESSION=default
WAHA_API_KEY=
WAHA_WEBHOOK_SECRET=

# LiveChat (isi jika ingin test LiveChat)
LIVECHAT_VERIFY_TOKEN=
LIVECHAT_WEBHOOK_SECRET=

# Agent default
AGENT_ID=1
AGENT_KODE=PG
```

---

### Menjalankan Queue Worker (Manual)

```bash
php artisan queue:work --tries=3 --backoff=60 --timeout=180
```

---

### Catatan Local

- `APP_DEBUG=true` — aktifkan agar error detail tampil di browser.
- `CACHE_STORE=file` — tidak butuh Redis. Jika Redis jalan, ganti ke `redis`.
- Webhook Telegram/WAHA/LiveChat butuh URL publik. Gunakan **ngrok** atau **Expose** untuk tunnel ke localhost.
- Jalankan `php artisan optimize:clear` setiap kali ubah file `.env`.

---

---

## PRODUCTION

### Prerequisites

- PHP 8.3+ (dengan ekstensi: `pdo`, `mbstring`, `openssl`, `redis`, `gd`, `curl`)
- Composer (mode no-dev)
- Node.js 20+ (hanya untuk build — tidak perlu di server)
- MySQL / PostgreSQL
- Redis (wajib untuk cache & queue)
- Supervisor (untuk menjaga queue worker tetap jalan)
- Web server: Nginx atau Apache

---

### Step-by-Step

```bash
# 1. Pull kode terbaru
git pull origin main

# 2. Install PHP dependencies (tanpa dev packages)
composer install --optimize-autoloader --no-dev

# 3. Build assets (jalankan di local lalu commit hasil build, ATAU build di server jika ada Node)
npm ci
npm run build

# 4. Salin dan isi .env
cp .env.example .env
# Edit .env sesuai konfigurasi production (lihat seksi ENV di bawah)

# 5. Generate application key (hanya sekali saat pertama deploy)
php artisan key:generate

# 6. Jalankan migrasi
php artisan migrate --force

# 7. Cache konfigurasi, route, view untuk performa optimal
php artisan optimize

# 8. Buat symlink storage (hanya sekali)
php artisan storage:link

# 9. Restart queue worker via Supervisor (lihat konfigurasi Supervisor di bawah)
sudo supervisorctl restart laravel-worker:*
```

---

### ENV yang Wajib Diisi (Production)

```env
APP_NAME=AI-Bot
APP_ENV=production
APP_KEY=                        # generate sekali, simpan baik-baik
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=warning               # jangan gunakan 'debug' di production

# Database — MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_bot_prod
DB_USERNAME=ai_bot_user
DB_PASSWORD=StrongPassword123!

# Queue — Redis
QUEUE_CONNECTION=redis
DB_QUEUE_RETRY_AFTER=210
REDIS_QUEUE_RETRY_AFTER=210
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Cache — Redis
CACHE_STORE=redis

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

# OpenAI
OPENAI_API_KEY=sk-...

# Telegram
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=        # random string panjang, simpan rahasia

# WhatsApp via WAHA
WAHA_BASE_URL=https://waha.yourdomain.com
WAHA_SESSION=default
WAHA_API_KEY=
WAHA_WEBHOOK_SECRET=            # random string panjang, simpan rahasia

# LiveChat
LIVECHAT_VERIFY_TOKEN=
LIVECHAT_WEBHOOK_SECRET=        # random string panjang, simpan rahasia

# Agent
AGENT_ID=1
AGENT_KODE=PG

# Retensi data
CONVERSATION_RETENTION_DAYS=90
CUSTOMER_MEMORY_RETENTION_DAYS=90

# Mail (sesuaikan provider: smtp, ses, mailgun, dll)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailprovider.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

### Konfigurasi Supervisor (Queue Worker)

Buat file `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ai-project/artisan queue:work redis --sleep=3 --tries=3 --backoff=60 --timeout=180 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ai-project/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Aktifkan Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

### Konfigurasi Cron (Scheduler)

Tambahkan ke crontab server (`crontab -e`):

```cron
* * * * * cd /var/www/ai-project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler ini menjalankan job `retention:prune` sesuai jadwal yang diset di `routes/console.php`.

---

### Konfigurasi Nginx (Contoh)

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/ai-project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

### Setelah Deploy Ulang (Update Kode)

```bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize
sudo supervisorctl restart laravel-worker:*
```

---

### Checklist Production

- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` sudah di-generate dan disimpan
- [ ] `LOG_LEVEL=warning`
- [ ] Database credentials diisi, koneksi berhasil
- [ ] Redis berjalan dan dapat diakses
- [ ] `TELEGRAM_WEBHOOK_SECRET`, `WAHA_WEBHOOK_SECRET`, `LIVECHAT_WEBHOOK_SECRET` diisi
- [ ] `OPENAI_API_KEY` valid
- [ ] Supervisor berjalan: `sudo supervisorctl status`
- [ ] Cron scheduler aktif: `crontab -l`
- [ ] HTTPS aktif (SSL via Certbot / reverse proxy)
- [ ] `php artisan optimize` sudah dijalankan setelah deploy
- [ ] `storage:link` sudah dibuat
