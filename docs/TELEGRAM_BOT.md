# Telegram Bot Integration Specification

## 1. Architectural Overview

The Telegram Bot serves as the primary digital bridge between the passenger's physical QR scan in the taxi/tuk-tuk and the company's internal translation service workflow.

```text
Passenger Smartphone Camera
           ↓
Scans QR Code: https://t.me/<BotUsername>?start=REF_TK874M
           ↓
Telegram App Opens -> Customer Clicks "START"
           ↓
Telegram Bot API sends Update to Backend
           ↓
Webhook / Long-Polling Ingestion Layer
           ↓
TelegramBotService:
  ├── 1. Parse /start payload ("REF_TK874M")
  ├── 2. Upsert TelegramUser profile
  ├── 3. Attribute Lead to Driver
  ├── 4. Send Welcome Response to Customer
  └── 5. Notify Staff Channel/Dashboard
```

---

## 2. Ingestion Modes: Webhook vs. Polling

To accommodate both local development and secure cloud production environments, the system supports dual ingestion strategies:

| Mode | Environment | Description |
| :--- | :--- | :--- |
| **Long Polling** | Local / Development | Run via artisan command: `php artisan telegram:poll`. Fetches updates via `getUpdates` API. Requires no public IP, SSL certificate, or tunneling tool (e.g. ngrok). |
| **Webhook** | Production / Staging | Telegram posts updates via HTTPS directly to `POST /api/telegram/webhook`. Fast, event-driven, handles concurrent volume. Secured with secret token verification (`X-Telegram-Bot-Api-Secret-Token`). |

---

## 3. Bot Command & Message Routing

### 3.1 The `/start` Deep-Link Handler
When a user begins the conversation with a referral deep link, Telegram transmits a message containing:
```text
/start REF_TK874M
```

#### Processing Steps:
1. **Payload Extraction:**
   - Command: `/start`
   - Parameter: `REF_TK874M`
2. **Token Verification:**
   - Query `referral_qr_codes` where `code = 'REF_TK874M'` and `is_active = true`.
   - If not found or inactive, log unknown referral and proceed as direct inquiry.
3. **User Profile Registration:**
   - Upsert `telegram_users` table with `telegram_id`, `first_name`, `last_name`, `username`, and `language_code`.
4. **Attribution & Lead Creation:**
   - Execute `ReferralAttributionService::attributeTelegramUser(telegramUser, qrCode)`.
   - Applies the 30-day window policy and records to `attribution_logs`.

---

## 4. Customer Greeting & Conversational UX

Upon receiving the initial `/start` scan, the bot immediately greets the customer in **English**, with supplementary **Khmer** and **Chinese** welcoming notes:

```text
👋 Welcome to Phnom Penh Translation & Legalization Services!
សូមស្វាគមន៍មកកាន់សេវាកម្មបកប្រែឯកសារ!
欢迎使用金边专业翻译与认证服务！

We specialize in:
✅ Certified Legal & Business Translation
✅ Visa & Embassy Document Translation
✅ Driving License & Notarized Documents
✅ Rapid Turnaround & Official Stamps

Our translation specialist has received your inquiry and will message you right here shortly to assist you with pricing and documents.

📍 Office: Phnom Penh, Cambodia
📞 Telegram / Hotline: +855 (0) XX XXX XXX
```

---

## 5. Staff Real-Time Notification Pipeline

When a customer initiates contact via a driver referral, staff must be alerted instantly to minimize lead response time:

1. **Staff Telegram Channel / Group Alert:**
   The bot automatically dispatches an alert to the internal Staff Telegram Group:
   ```text
   🚨 NEW DRIVER REFERRAL LEAD!
   ━━━━━━━━━━━━━━━━━━━━━━
   👤 Customer: John Doe (@johndoe)
   🚖 Driver: Chea Sokha (Tuk-Tuk #289)
   🏷️ Referral Code: REF_TK874M
   ⏰ Time: Oct 6, 2026, 10:15 AM
   
   👉 Open in Staff Dashboard to assign & quote:
   https://staff.translation.com.kh/leads/LEAD-202610-0012
   ```

2. **Web Dashboard Real-time Badge:**
   The web staff portal highlights new incoming leads in the top navigation badge and the Lead Kanban board.

---

## 6. Testing & Simulation Architecture

To enable reliable automated testing without hitting live Telegram servers:
1. **Mock Telegram Client (`TelegramClientFake`):**
   - Implements identical interface as live HTTP client.
   - Captures dispatched messages in an internal array for assertion in PHPUnit / Pest tests.
2. **Artisan Lead Simulation Command:**
   - `php artisan telegram:simulate-scan {referral_code} {--telegram_id=}`
   - Allows QA and developers to simulate a driver scan locally in 1 second.
