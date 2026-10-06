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

## 2. Webhook Ingestion & Security

The system processes incoming Telegram updates via HTTPS webhook:
- **Route:** `POST /api/telegram/webhook`
- **Controller:** `App\Http\Controllers\TelegramWebhookController`
- **Security:** Verified with the header `X-Telegram-Bot-Api-Secret-Token` matched against `config('services.telegram.webhook_secret')`.
- **Idempotency:** Every update ID (`update_id`) is tracked in `referral_events.telegram_update_id`. If Telegram retries a previously delivered update, the webhook acknowledges immediately with no duplicate lead creation.
- **Fail-Safe Response:** Returns HTTP 200 to Telegram API to prevent repeated retry flooding, while logging any unhandled internal exceptions safely. Outbound Telegram API failures never abort database transactions.

### Setting up Production Webhook:
Run Telegram API `setWebhook` endpoint with your bot token and secret token:
```bash
curl -F "url=https://your-domain.com/api/telegram/webhook" \
     -F "secret_token=YOUR_CONFIGURED_WEBHOOK_SECRET" \
     https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook
```

---

## 3. Bot Command & Message Routing

### 3.1 The `/start` Deep-Link Handler
When a user begins the conversation with a referral deep link, Telegram transmits:
```text
/start REF_TK874M
```

#### Processing Steps:
1. **Payload Extraction:**
   - Command: `/start`
   - Parameter: `REF_TK874M` (normalized to uppercase)
2. **Token Verification:**
   - Query `referral_codes` where `code = 'REF_TK874M'`.
   - If not found: records event `UNKNOWN_CODE` and sends invalid referral response.
   - If inactive: records event `INACTIVE_CODE` and sends disabled code response.
   - If active: validates hierarchical partner, driver, and vehicle ownership.
3. **User Profile Registration:**
   - Upsert `telegram_users` table by `telegram_id`, updating `first_name`, `last_name`, `username`, `language_code`, and `last_interaction_at`.
4. **Attribution & Lead Pipeline:**
   - Execute `ReferralAttributionService::processWebhookUpdate()`.
   - Checks for existing active opportunity:
     - **FIRST QUALIFIED REFERRAL WINS:** If active lead exists, preserves original attribution, records `REPEAT_SCAN`, and keeps existing attribution intact.
     - If no active lead exists: creates Customer (if needed), creates Lead (`status = NEW`), creates `ReferralAttribution` (`status = ACTIVE`), and attaches attribution to the lead.
5. **Customer Confirmation:**
   - Dispatches multi-lingual welcome response via `TelegramBotService`.

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
