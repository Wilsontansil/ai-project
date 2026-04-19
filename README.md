# xoneBot — AI Chatbot Platform

Laravel 13 / PHP 8.3 API that connects **Telegram**, **WhatsApp (WAHA)**, and **LiveChat** to an OpenAI-powered assistant. Includes a backoffice for managing tools, data models, forbidden behaviours, and project settings.

## Architecture

```
Telegram / WhatsApp / LiveChat
        ↓ webhook
   Webhook Controller  →  AIService  →  OpenAI (gpt-4.1-mini)
        ↓                      ↓
   ResilientHttp          Tool Execution (info / get / update)
        ↓                      ↓
   Reply → Platform API   ConversationHistory (20 msgs, 12h TTL, channel-isolated)
```

## Requirements

- PHP 8.3+
- Composer 2
- SQLite (default) or MySQL/PostgreSQL
- Node.js 20+ & npm (for Vite assets)

## Quick Start

```bash
# 1. Clone & install
git clone <repo-url> && cd ai-project
composer install
npm install && npm run build

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure .env — set at minimum:
#    OPENAI_API_KEY, TELEGRAM_BOT_TOKEN, WAHA_BASE_URL,
#    and the three WEBHOOK_SECRET values

# 4. Database
php artisan migrate --seed

# 5. Run (starts server + queue worker + log viewer + vite)
composer dev
```

Or run individually:

```bash
php artisan serve              # HTTP server
php artisan queue:listen       # Queue worker (required for Telegram & WhatsApp replies)
npm run dev                    # Vite assets
```

## Environment Variables

| Variable                         | Purpose                                                  |
| -------------------------------- | -------------------------------------------------------- |
| `OPENAI_API_KEY`                 | OpenAI API key                                           |
| `TELEGRAM_BOT_TOKEN`             | Telegram Bot API token                                   |
| `TELEGRAM_WEBHOOK_SECRET`        | Telegram webhook auth secret                             |
| `WAHA_BASE_URL`                  | WAHA instance URL                                        |
| `WAHA_SESSION`                   | WAHA session name (default: `default`)                   |
| `WAHA_API_KEY`                   | WAHA API key                                             |
| `WAHA_WEBHOOK_SECRET`            | WhatsApp webhook auth secret                             |
| `WHATSAPP_AGENT`                 | WhatsApp agent code (default: `PG`)                      |
| `LIVECHAT_VERIFY_TOKEN`          | LiveChat challenge verification token                    |
| `LIVECHAT_WEBHOOK_SECRET`        | LiveChat webhook auth secret                             |
| `QUEUE_CONNECTION`               | Queue driver (default: `database`)                       |
| `CONVERSATION_RETENTION_DAYS`    | Auto-prune conversations older than N days (default: 90) |
| `CUSTOMER_MEMORY_RETENTION_DAYS` | Auto-prune stale customer memory (default: 90)           |

All settings can also be managed from the backoffice Settings page (stored in `project_settings`, takes priority over `.env`).

## API Endpoints

| Method   | Path                    | Purpose                                                    |
| -------- | ----------------------- | ---------------------------------------------------------- |
| GET      | `/api/test`             | Health check                                               |
| POST     | `/api/telegram/webhook` | Telegram webhook (auth: `X-Telegram-Bot-Api-Secret-Token`) |
| GET/POST | `/api/whatsapp/webhook` | WAHA webhook (auth: `X-Secret-Token`)                      |
| GET/POST | `/api/livechat/webhook` | LiveChat webhook (auth: `X-livechat-Token`)                |

## Backoffice

All routes under `/backoffice` require authentication. The UI is mobile-responsive (off-canvas sidebar on < 1024px).

| Path                               | Purpose                                                   |
| ---------------------------------- | --------------------------------------------------------- |
| `/backoffice/login`                | Admin login (username, rate-limited: 5 attempts / 15 min) |
| `/backoffice`                      | Customer dashboard & stats                                |
| `/backoffice/users`                | User management with role assignment                      |
| `/backoffice/ai-agent`             | Agent persona settings                                    |
| `/backoffice/chat-agents`          | Chat agent CRUD with duplication                          |
| `/backoffice/tools`                | CRUD for AI tools                                         |
| `/backoffice/data-models`          | CRUD for data model schemas                               |
| `/backoffice/database-connections` | CRUD for database connections                             |
| `/backoffice/forbidden-behaviours` | CRUD for banned behaviour rules                           |
| `/backoffice/settings`             | Global project settings                                   |
| `/backoffice/metrics`              | Observability dashboard (throughput, latency, cost)       |
| `/backoffice/locale/{locale}`      | Language toggle (id/en)                                   |

## Testing

```bash
# Run all tests
php artisan test

# Run specific suites
php artisan test tests/Feature/Webhooks
php artisan test tests/Feature/Backoffice
php artisan test tests/Feature/Console
```

## Static Analysis

```bash
composer analyse
# or directly:
vendor/bin/phpstan analyse --memory-limit=512M
```

PHPStan is configured at **level 5** with Larastan.

## Data Retention

The `retention:prune` command runs daily at 03:00 via the scheduler. Ensure the server cron calls:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Manual run: `php artisan retention:prune` (add `--dry-run` to preview).

## Deployment

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
```

Post-deploy — start the queue worker (required for Telegram & WhatsApp replies):

```bash
php artisan queue:work --tries=3
```

Ensure the server cron calls the scheduler every minute (required for data retention):

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Ensure `APP_DEBUG=false` and `LOG_LEVEL=warning` (or `error`) in production.

## Security

- **Webhook authentication** — Each channel has dedicated middleware verifying a shared secret header with `hash_equals()`. Telegram also validates request timestamp (300s tolerance) to prevent replay attacks.
- **Login via username** — Authentication uses username (not email). Rate-limited: 5 attempts / 15-minute lockout with dual-key throttle (username + IP).
- **Single-session enforcement** — Only one active browser session per user. Logging in from a new device invalidates the previous session via `SingleSession` middleware.
- **401 auto-redirect** — Unauthorized requests are automatically redirected to the login page.
- **Role-based access control** — Spatie Permission with roles (admin, operator) and 9 granular permissions.
- **Safe logging** — `LogSanitizer` redacts PII (names, emails, phones, tokens, message content) before any payload is logged.
- **Error handling** — API routes return sanitised JSON errors; debug details only in non-production.
- **Outbound resilience** — `ResilientHttp` with retry (3 attempts), exponential backoff, and circuit breaker (opens after 5 failures for 60s).
- **Async processing** — Telegram & WhatsApp webhooks dispatch a `ProcessAiReply` queue job (3 tries, 10/30s backoff) instead of blocking the webhook response. LiveChat stays synchronous (reply is in HTTP response body).
- **Channel-isolated conversations** — Chat history and debounce cache keys are prefixed with the channel name, preventing cross-platform history leakage between users with identical IDs on different platforms.
- **Conversation cap** — 50 messages per conversation per day to limit storage growth.
- **Data retention** — `retention:prune` auto-deletes old conversations and stale customer memory (configurable, default 90 days).

## Further Reading

See [PROJECT_GUIDE.md](PROJECT_GUIDE.md) for detailed architecture, model schemas, tool types, field definitions, and coding conventions.
