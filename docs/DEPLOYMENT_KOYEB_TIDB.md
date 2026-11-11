# Option 2 Deployment Guide: Koyeb / Render (App) + TiDB Cloud (Database)

This guide walks you through the easiest 100% free cloud deployment for the **Telegram Referral & Commission Management System** without needing to manage Linux servers or install packages manually.

---

## Architecture Overview

```text
┌───────────────────────────────┐
│     Customer Smartphone       │
└──────────────┬────────────────┘
               │ Scans QR / Telegram
               ▼
┌───────────────────────────────┐
│      Telegram Bot API         │
└──────────────┬────────────────┘
               │ HTTPS Webhook (Auto SSL)
               ▼
┌───────────────────────────────┐
│   Koyeb or Render (App)       │
│  • PHP 8.4-FPM + Nginx        │
│  • Vue 3 / Inertia Built      │
│  • Automatic GitHub Deploys   │
└──────────────┬────────────────┘
               │ Secure MySQL SSL Connection (Port 4000)
               ▼
┌───────────────────────────────┐
│    TiDB Cloud Serverless      │
│  • Free 5 GB Storage          │
│  • MySQL 8 Compatible         │
│  • No Credit Card Needed      │
└───────────────────────────────┘
```

---

## Part 1: Create Free MySQL Database on TiDB Cloud (2 Minutes)

TiDB Cloud Serverless provides a free, fully managed database compatible with MySQL 8.

1. Go to [tidbcloud.com](https://tidbcloud.com/) and click **Sign Up** (Sign in with Google or GitHub). **No credit card required.**
2. On your dashboard, click **Create Cluster**.
3. Select **Serverless (Free Tier)**:
   - **Cluster Name:** `qr-translation-db`
   - **Cloud Provider & Region:** Select **AWS / Singapore (`ap-southeast-1`)** or **Tokyo** for the lowest latency.
4. Click **Create**. It takes ~15 seconds to be ready.
5. Under cluster overview, click the **Connect** button on the top right:
   - **Connect with:** Select **General** or **PHP (PDO)**.
   - Click **Generate Password** and copy your credentials:
     - **Host:** e.g. `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`
     - **Port:** `4000`
     - **User:** e.g. `xxxxxx.root`
     - **Password:** `<Your-Generated-Password>`
     - **Database:** `test` (or create a database named `translation`)

---

## Part 2: Deploy App on Koyeb (Recommended)

Koyeb offers a free Eco instance that stays online 24/7 without cold starts.

1. Go to [koyeb.com](https://www.koyeb.com/) and sign up with GitHub.
2. In the Koyeb Control Panel, click **Create App**.
3. Select **GitHub** as the deployment source and authorize Koyeb to access your repository (`qr-translation-service`).
4. In the configuration settings:
   - **Deployment Method:** Select **Dockerfile** (Koyeb detects the `Dockerfile` in the root).
   - **Instance Type:** Select **Eco (Free)**.
   - **Region:** Select **Singapore (`sin`)** or **Tokyo (`tyo`)** (matching your TiDB region).
   - **Port:** Under *Ports*, set the internal port to **80** (Protocol: HTTP). Koyeb automatically provisions a public HTTPS domain.
5. Click **Environment Variables** and add the following keys:

| Key | Example Value | Description |
| :--- | :--- | :--- |
| `APP_NAME` | `DIN & CO Translation` | Application Title |
| `APP_ENV` | `production` | Production mode |
| `APP_DEBUG` | `false` | Disable debug bar |
| `APP_KEY` | `base64:...` | Generate with `php artisan key:generate --show` |
| `APP_URL` | `https://<your-app-name>.koyeb.app` | Your Koyeb generated HTTPS URL |
| `DB_CONNECTION` | `mysql` | MySQL PDO driver |
| `DB_HOST` | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` | TiDB Host |
| `DB_PORT` | `4000` | TiDB Port (4000) |
| `DB_DATABASE` | `test` | TiDB Database |
| `DB_USERNAME` | `<xxxxxx>.root` | TiDB Username |
| `DB_PASSWORD` | `<your-tidb-password>` | TiDB Password |
| `MYSQL_ATTR_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` | Enables required SSL for TiDB |
| `RUN_MIGRATIONS` | `true` | Runs database migrations on container start |
| `TELEGRAM_BOT_TOKEN` | `YOUR_BOT_TOKEN` | From @BotFather |
| `TELEGRAM_BOT_USERNAME` | `DinCoTranslationBot` | Your Bot username |
| `TELEGRAM_WEBHOOK_SECRET` | `RANDOM_32_CHAR_SECRET_KEY` | Webhook verification secret |
| `TELEGRAM_VERIFY_SSL` | `true` | Require valid SSL for webhook |

6. Click **Deploy**. Koyeb will automatically build the multi-stage Docker container and launch your application.

---

## Alternative: Deploy App on Render

If you prefer Render:

1. Sign up at [render.com](https://render.com/) and click **New + > Web Service**.
2. Connect your GitHub repository.
3. Settings:
   - **Language / Runtime:** **Docker**
   - **Region:** Singapore
   - **Instance Type:** **Free**
4. Under **Environment Variables**, add the same environment variables as listed above (Set `APP_URL=https://your-service-name.onrender.com`).
5. Click **Create Web Service**.

---

## Part 3: Activate the Telegram Webhook

Once your Koyeb or Render service is deployed and showing **Healthy (Green)**:

You can register the webhook with Telegram in one click by running this HTTP call in your browser or terminal (replace with your real Koyeb/Render domain and Telegram Bot Token):

```bash
curl -F "url=https://YOUR_APP.koyeb.app/api/telegram/webhook" \
     -F "secret_token=YOUR_CONFIGURED_WEBHOOK_SECRET" \
     https://api.telegram.org/bot<YOUR_TELEGRAM_BOT_TOKEN>/setWebhook
```

Telegram will respond:
```json
{
  "ok": true,
  "result": true,
  "description": "Webhook was set"
}
```

---

## Part 4: Default Admin Login

When migrations with seeders run (`php artisan migrate --seed`), your default administrative accounts are:
- **Super Admin:** `superadmin@translation.local` / `Password123!`
- **Operations Admin:** `admin@translation.local` / `Password123!`
*(Please log in and update the password or email immediately in Profile Settings!)*
