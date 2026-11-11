#!/bin/bash
# ==============================================================================
# Automated Server Setup Script for Oracle Cloud Ubuntu VPS (24.04 / 22.04 LTS)
# Installs: PHP 8.4 + Extensions, Nginx, MySQL 8, Composer, Node.js 22, Certbot
# ==============================================================================

set -e

echo "🚀 Starting server setup for QR Translation Service..."

# 1. Update OS packages
echo "📦 Updating OS packages..."
sudo apt update && sudo apt upgrade -y

# 2. Add PHP repository for PHP 8.4
echo "🐘 Adding ondrej/php PPA..."
sudo apt install -y software-properties-common curl git unzip nginx supervisor iptables-persistent
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# 3. Install PHP 8.4 & Extensions
echo "🐘 Installing PHP 8.4 and required extensions..."
sudo apt install -y \
    php8.4 \
    php8.4-fpm \
    php8.4-cli \
    php8.4-common \
    php8.4-mysql \
    php8.4-zip \
    php8.4-gd \
    php8.4-mbstring \
    php8.4-curl \
    php8.4-xml \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-sqlite3

# 4. Install Composer
echo "🎼 Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
fi

# 5. Install Node.js 22 & npm
echo "🟩 Installing Node.js 22 LTS..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
    sudo apt install -y nodejs
fi

# 6. Install MySQL Server 8
echo "🗄️ Installing MySQL Server..."
sudo apt install -y mysql-server

# 7. Install Certbot for Let's Encrypt SSL
echo "🔒 Installing Certbot..."
sudo apt install -y certbot python3-certbot-nginx

# 8. Configure Ubuntu Firewall for HTTP and HTTPS
echo "🛡️ Configuring internal firewall for Ports 80 and 443..."
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 80 -j ACCEPT || true
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 443 -j ACCEPT || true
sudo netfilter-persistent save || true

# 9. Verify installations
echo ""
echo "=========================================================="
echo "✅ Server dependencies installed successfully!"
echo "PHP version:       $(php -v | head -n 1)"
echo "Composer version:  $(composer --version | head -n 1)"
echo "Node version:      $(node -v)"
echo "NPM version:       $(npm -v)"
echo "Nginx status:      $(systemctl is-active nginx)"
echo "MySQL status:      $(systemctl is-active mysql)"
echo "=========================================================="
echo "Next step: Configure MySQL database and deploy your project into /var/www/qr-translation"

