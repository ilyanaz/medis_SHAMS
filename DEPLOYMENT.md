# DigitalOcean Deployment

Target server: `157.230.36.174`

## Server Stack

- Ubuntu 24.04 LTS
- Nginx
- PHP 8.2 or 8.3 with `php-fpm`
- MySQL Server
- Composer
- Node.js 20+

## First-Time Server Setup

```bash
sudo apt update
sudo apt install -y nginx mysql-server unzip git
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
cd /tmp && curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## App Deployment

```bash
cd /var/www
sudo git clone <your-repository-url> medisSHAMS
cd medisSHAMS
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

## Database

```bash
mysql -u root -p
CREATE DATABASE medis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
mysql -u root -p medis < database/schema/medis.sql
```

## Laravel Permissions

```bash
sudo chown -R www-data:www-data /var/www/medisSHAMS
sudo chmod -R 775 /var/www/medisSHAMS/storage
sudo chmod -R 775 /var/www/medisSHAMS/bootstrap/cache
```

## Nginx Example

```nginx
server {
    listen 80;
    server_name 157.230.36.174;

    root /var/www/medisSHAMS/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Final Production Commands

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
