# Oracle Cloud Always Free Production Deployment Guide

This guide walks you through deploying the **Telegram Referral & Commission Management System** on **Oracle Cloud Infrastructure (OCI) Always Free Tier** using an Ubuntu VPS.

---

## 1. Oracle Cloud Always Free Specifications

Oracle Cloud offers one of the most generous permanent free tiers:
- **Compute Instance (Recommended):** Ampere ARM (`VM.Standard.A1.Flex`)
  - **Cores:** Up to 4 OCPUs (Free forever)
  - **RAM:** Up to 24 GB RAM (Free forever)
  - **Storage:** Up to 200 GB Boot Volume
  - **Alternative (if ARM is out of stock in your region):** AMD (`VM.Standard.E2.1.Micro`) with 1 OCPU and 1 GB RAM.
- **Operating System:** Ubuntu 24.04 LTS or Ubuntu 22.04 LTS
- **IP Address:** 1 Free Static Ephemeral/Reserved Public IPv4

---

## 2. Launching Your Free Instance in Oracle Cloud

### Step 2.1: Sign Up
1. Visit [oracle.com/cloud/free](https://www.oracle.com/cloud/free/) and sign up.
2. Choose your **Home Region** carefully (Select **Singapore**, **Tokyo**, or **Seoul** for the lowest network latency to Cambodia).
3. Complete the verification (a valid debit/credit card is required for identity check, but your card will **not** be charged for Always Free resources).

### Step 2.2: Create Compute Instance
1. In the OCI Console, navigate to **Compute > Instances > Create Instance**.
2. **Name:** `qr-translation-prod`
3. **Placement & Availability Domain:** Leave default (AD-1).
4. **Image and Shape:**
   - **Image:** Click *Change Image* -> Choose **Canonical Ubuntu 24.04** (or 22.04).
   - **Shape:** Click *Change Shape* -> Select **Ampere (Arm-based)** -> `VM.Standard.A1.Flex` -> Set **2 OCPUs** and **12 GB RAM** (or 4 OCPUs / 24 GB).
5. **Networking:**
   - Create a new Virtual Cloud Network (VCN) and public subnet.
   - Ensure **Assign a public IPv4 address** is checked.
6. **Add SSH Keys:**
   - Select **Generate a key pair for me**.
   - Click **Save Private Key** (e.g. `ssh-key.key`). *Keep this file safe on your computer!*
7. Click **Create** and wait 1–2 minutes until the status becomes **Running** (Green).
8. Copy the **Public IP Address** (e.g. `129.150.x.x`).

---

## 3. Configuring Firewalls (OCI Ingress & Ubuntu iptables)

> [!IMPORTANT]
> Oracle Cloud has **two** firewalls:
> 1. The OCI Virtual Cloud Network (Cloud Firewall)
> 2. The internal Ubuntu OS firewall (`iptables`)

### Step 3.1: Open Ports in OCI Security List
1. In the OCI Console, click on your instance's **Virtual Cloud Network (VCN)**.
2. Click on the **Public Subnet** -> Click on the **Default Security List**.
3. Under **Ingress Rules**, click **Add Ingress Rules**:
   - **Rule 1 (HTTP):**
     - Source CIDR: `0.0.0.0/0`
     - IP Protocol: `TCP`
     - Destination Port Range: `80`
     - Description: `Allow HTTP traffic`
   - **Rule 2 (HTTPS):**
     - Source CIDR: `0.0.0.0/0`
     - IP Protocol: `TCP`
     - Destination Port Range: `443`
     - Description: `Allow HTTPS for Telegram Webhook and Web App`

### Step 3.2: Connect via SSH
From your local Windows terminal (PowerShell or Git Bash):
```powershell
ssh -i "path\to\ssh-key.key" ubuntu@YOUR_INSTANCE_PUBLIC_IP
```

### Step 3.3: Open Firewall Inside Ubuntu
Oracle's default Ubuntu image restricts incoming traffic. Run these commands on your VPS:
```bash
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 80 -j ACCEPT
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 443 -j ACCEPT
sudo netfilter-persistent save
```

---

## 4. Automated Server Provisioning Script

Run the following commands on your Ubuntu VPS to install PHP 8.4, Nginx, MySQL 8, Composer, Node.js, and Certbot:

```bash
# 1. Update system packages
sudo apt update && sudo apt upgrade -y

# 2. Add PHP repository for PHP 8.4
sudo apt install -y software-properties-common curl git unzip nginx supervisor
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# 3. Install PHP 8.4 and required extensions
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-common php8.4-mysql \
    php8.4-zip php8.4-gd php8.4-mbstring php8.4-curl php8.4-xml php8.4-bcmath \
    php8.4-intl php8.4-sqlite3

# 4. Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# 5. Install Node.js 22 LTS & npm
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# 6. Install MySQL Server 8
sudo apt install -y mysql-server

# 7. Install Certbot (Let's Encrypt SSL)
sudo apt install -y certbot python3-certbot-nginx
```

---

## 5. Database Configuration

1. Open MySQL root prompt:
   ```bash
   sudo mysql
   ```
2. Create the production database and dedicated user:
   ```sql
   CREATE DATABASE translation_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'translation_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD_HERE';
   GRANT ALL PRIVILEGES ON translation_prod.* TO 'translation_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

---

## 6. Deploying the Application

### Step 6.1: Clone the Codebase
```bash
sudo mkdir -p /var/www/qr-translation
sudo chown -R ubuntu:ubuntu /var/www/qr-translation

# Clone your repository (or push your repository to GitHub / Gitlab and clone here)
git clone https://github.com/your-username/qr-translation-service.git /var/www/qr-translation
cd /var/www/qr-translation
```

### Step 6.2: Environment Configuration
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
nano .env
```

Set the production variables:
```dotenv
APP_NAME="DIN & CO Translation"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=translation_prod
DB_USERNAME=translation_user
DB_PASSWORD=YOUR_SECURE_PASSWORD_HERE

TELEGRAM_BOT_TOKEN="YOUR_TELEGRAM_BOT_TOKEN"
TELEGRAM_BOT_USERNAME="DinCoTranslationBot"
TELEGRAM_WEBHOOK_SECRET="GENERATE_A_RANDOM_32_CHAR_SECRET_STRING"
TELEGRAM_VERIFY_SSL=true

COMPANY_NAME="DIN & CO Translation"
COMPANY_PHONE="(+855) 12 762 772"
COMPANY_ADDRESS="No. 123, Preah Monivong Blvd, Phnom Penh, Cambodia"
```

### Step 6.3: Install Dependencies & Build Frontend
```bash
# Generate application encryption key
php artisan key:generate

# Install PHP production packages
composer install --no-dev --optimize-autoloader

# Install Frontend dependencies and build production assets
npm install
npm run build

# Run database migrations and seeders
php artisan migrate --force --seed

# Create storage symlink
php artisan storage:link

# Set file permissions for Nginx and PHP-FPM
sudo chown -R www-data:www-data /var/www/qr-translation/storage /var/www/qr-translation/bootstrap/cache
sudo chmod -R 775 /var/www/qr-translation/storage /var/www/qr-translation/bootstrap/cache

# Cache routes and configurations for maximum performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 7. Nginx & Domain SSL Setup

### Step 7.1: Point Your Domain / Subdomain
In your DNS management (e.g. Cloudflare, Namecheap, Google Domains):
- Create an **A record**:
  - **Host / Name:** `bot` (or `@` for root domain)
  - **Value / IPv4:** `YOUR_ORACLE_INSTANCE_PUBLIC_IP`
  - **TTL:** Auto

*(If you don't own a custom domain, you can register a free subdomain at [DuckDNS](https://www.duckdns.org)).*

### Step 7.2: Create Nginx Server Block
```bash
sudo nano /etc/nginx/sites-available/qr-translation
```

Paste the following configuration (replace `your-domain.com` with your real domain):
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    root /var/www/qr-translation/public;

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
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/qr-translation /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 7.3: Issue Free SSL Certificate (Certbot)
Telegram requires a valid HTTPS SSL certificate for webhooks:
```bash
sudo certbot --nginx -d your-domain.com
```
Follow the prompt. Certbot will automatically configure HTTPS on Nginx and set up automatic certificate renewal.

---

## 8. Activating the Telegram Webhook

Once your domain is live over HTTPS, register the webhook with the Telegram Bot API:

```bash
cd /var/www/qr-translation

# Register the webhook
php artisan telegram:webhook --set

# Verify webhook status
php artisan telegram:webhook --info
```

You should see:
```text
🤖 Telegram Bot: @DinCoTranslationBot
Registering webhook: https://your-domain.com/api/telegram/webhook...
✅ Webhook registered successfully!
{
    "ok": true,
    "result": true,
    "description": "Webhook was set"
}
```

---

## 9. Configuring Cron & Background Tasks

To ensure scheduled notifications and scans run smoothly, add the Laravel Scheduler to the system crontab:

```bash
sudo crontab -e -u www-data
```
Add this line:
```cron
* * * * * cd /var/www/qr-translation && php artisan schedule:run >> /dev/null 2>&1
```

---

## 10. Summary of Ongoing Maintenance Commands

When you push new updates from development:
```bash
cd /var/www/qr-translation
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan optimize
```

