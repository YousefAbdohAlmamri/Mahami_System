# Mahami Platform — Production Deployment Guide

This guide provides instructions for deploying the **Mahami (مهامي)** platform to a production environment.

---

## 1. System Requirements

- **Operating System:** Ubuntu 22.04 LTS / 24.04 LTS or compatible Linux distribution.
- **Web Server:** Nginx 1.20+ or Apache 2.4+
- **PHP:** PHP 8.2 or 8.3 with extensions:
  - `php-fpm`, `php-mysql`, `php-xml`, `php-mbstring`, `php-curl`, `php-zip`, `php-gd`, `php-intl`, `php-bcmath`
- **Database:** MariaDB 10.6+ or MySQL 8.0+
- **Process Manager:** Supervisor (for background queues)
- **Node.js:** Node.js 18 LTS or 20 LTS & npm (for frontend asset compilation)
- **Composer:** Composer 2.6+

---

## 2. Backend Deployment Steps (Laravel)

### Step 2.1: Clone & Install Dependencies
```bash
cd /var/www/mahami/Mahami-API
composer install --no-dev --optimize-autoloader
```

### Step 2.2: Environment Configuration
Copy `.env.example` to `.env` and configure:
```bash
cp .env.example .env
php artisan key:generate
```

Ensure the following production settings are in `.env`:
```ini
APP_NAME="Mahami"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.mahami.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mahami_production
DB_USERNAME=mahami_user
DB_PASSWORD="SECURE_DATABASE_PASSWORD"

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

SANCTUM_STATEFUL_DOMAINS=app.mahami.yourdomain.com
CORS_ALLOWED_ORIGINS=https://app.mahami.yourdomain.com
```

### Step 2.3: Database Migrations
Run the migrations to create all 22 application tables:
```bash
php artisan migrate --force
```

### Step 2.4: Storage Symlink & Permissions
```bash
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Step 2.5: Production Optimizations
Cache routes, config, and views for optimal performance:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 3. Scheduled Tasks & Cron Configuration

Add the Laravel scheduler to the server's crontab (`crontab -e -u www-data`):
```bash
* * * * * cd /var/www/mahami/Mahami-API && php artisan schedule:run >> /dev/null 2>&1
```

To schedule due date reminders, add this task in `app/Console/Kernel.php` or `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('mahami:send-due-reminders')->dailyAt('08:00');
```

---

## 4. Background Queues with Supervisor

Create a Supervisor worker configuration at `/etc/supervisor/conf.d/mahami-worker.conf`:
```ini
[program:mahami-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mahami/Mahami-API/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/mahami/Mahami-API/storage/logs/worker.log
stopwaitsecs=3600
```

Start the worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mahami-worker:*
```

---

## 5. Frontend Deployment Steps (Vue 3 / Vite)

### Step 5.1: Build Frontend Assets
```bash
cd /var/www/mahami/Mahami-Frontend
npm ci
npm run build
```
The compiled SPA is located in `Mahami-Frontend/dist/`.

---

## 6. Nginx Web Server Configuration

### 6.1 Frontend Site (`/etc/nginx/sites-available/mahami-frontend`)
```nginx
server {
    listen 80;
    server_name app.mahami.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.mahami.yourdomain.com;

    root /var/www/mahami/Mahami-Frontend/dist;
    index index.html;

    # SSL Certificates (Certbot)
    ssl_certificate /etc/letsencrypt/live/app.mahami.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.mahami.yourdomain.com/privkey.pem;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 6.2 Backend API (`/etc/nginx/sites-available/mahami-api`)
```nginx
server {
    listen 80;
    server_name api.mahami.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.mahami.yourdomain.com;

    root /var/www/mahami/Mahami-API/public;
    index index.php;

    client_max_body_size 50M;

    ssl_certificate /etc/letsencrypt/live/api.mahami.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.mahami.yourdomain.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Enable the sites and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/mahami-frontend /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/mahami-api /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```
