# AI Project Guide

## Project Overview

Laravel 13 API that connects Telegram, WhatsApp (WAHA), and LiveChat messages to an OpenAI-powered assistant (xoneBot). Includes a full backoffice for managing tools, data models, forbidden behaviours, and global settings.

## Architecture

```
Telegram / WhatsApp / LiveChat
        ↓ webhook (authenticated via middleware)
   Webhook Controller
        ↓
   ┌─────────────────────────────────────────────┐
   │ Telegram & WhatsApp (push-based)            │
   │   → ProcessAiReply queue job (async)        │
   │   → returns 200 immediately                 │
   ├─────────────────────────────────────────────┤
   │ LiveChat (response-based)                   │
   │   → AIService::reply() inline (sync)        │
   │   → reply is in HTTP response body          │
   └─────────────────────────────────────────────┘
        ↓
   AIService  →  OpenAI (gpt-4.1-mini)
        ↓
   Agent Context (identity, memory, behavior)
        ↓
   Tool Execution (info / get / update)
        ↓
   ResilientHttp  →  Platform send API
        ↓
   MetricsCollector  →  bot_metrics table
```

## API Endpoints

| Method   | Path                    | Controller         | Purpose               |
| -------- | ----------------------- | ------------------ | --------------------- |
| GET      | `/api/test`             | closure            | Health check          |
| POST     | `/api/telegram/webhook` | TelegramController | Telegram webhook      |
| GET/POST | `/api/whatsapp/webhook` | WhatsAppController | WAHA WhatsApp webhook |
| GET/POST | `/api/livechat/webhook` | LiveChatController | LiveChat webhook      |

## Backoffice Routes

All backoffice routes require authentication (`auth` + `set.locale` middleware). The UI is mobile-responsive with an off-canvas sidebar drawer on screens < 1024px.

| Path                               | Controller                   | Purpose                        |
| ---------------------------------- | ---------------------------- | ------------------------------ |
| `/backoffice/login`                | AuthController               | Admin login (rate-limited)     |
| `/backoffice`                      | DashboardController          | Customer dashboard + stats     |
| `/backoffice/customer/{id}/chat`   | DashboardController          | Chat history view              |
| `/backoffice/ai-agent`             | AIAgentController            | Agent persona settings         |
| `/backoffice/chat-agents`          | ChatAgentController          | Chat agent CRUD + duplication  |
| `/backoffice/tools`                | ToolController               | CRUD for AI tools              |
| `/backoffice/tools/test-endpoint`  | ToolController               | Test tool HTTP endpoint        |
| `/backoffice/data-models`          | DataModelController          | CRUD for data models           |
| `/backoffice/database-connections` | DatabaseConnectionController | CRUD for database connections  |
| `/backoffice/forbidden-behaviours` | ForbiddenBehaviourController | CRUD for banned behavior rules |
| `/backoffice/settings`             | SettingController            | Global project settings        |
| `/backoffice/metrics`              | MetricsController            | Observability dashboard        |
| `/backoffice/locale/{locale}`      | LocaleController             | Language toggle (id/en)        |

## Webhook Authentication Middleware

Each webhook channel has a dedicated middleware that verifies a shared secret header using `hash_equals()`. Requests without a valid secret are rejected with 403.

| Middleware              | Header                            | Notes                                                            |
| ----------------------- | --------------------------------- | ---------------------------------------------------------------- |
| `VerifyTelegramWebhook` | `X-Telegram-Bot-Api-Secret-Token` | Also checks timestamp (300s tolerance) to prevent replay attacks |
| `VerifyWhatsAppWebhook` | `X-Secret-Token`                  | GET requests (health checks) bypass auth                         |
| `VerifyLiveChatWebhook` | `X-livechat-Token`                | Challenge/verification requests pass through                     |

Middleware aliases registered in `bootstrap/app.php`: `verify.telegram`, `verify.whatsapp`, `verify.livechat`.

Additional middleware:

| Middleware  | Alias        | Purpose                                     |
| ----------- | ------------ | ------------------------------------------- |
| `SetLocale` | `set.locale` | Sets app locale from session (`en` or `id`) |

## Controllers

### Webhook Controllers

- **TelegramController** — Validates payload, debounces rapid messages, dispatches `ProcessAiReply` queue job (async). Returns 200 immediately.
- **WhatsAppController** — Accepts WAHA payloads, filters events, deduplicates messages (cache-based, 5-min TTL), debounces, dispatches `ProcessAiReply` queue job (async). Returns 200 immediately.
- **LiveChatController** — Handles challenge/verification, calls `AIService::reply()` synchronously (reply is in the HTTP response body — cannot be async).

### Backoffice Controllers

- **AuthController** — Login/logout with session auth. Rate-limited: 5 failed attempts → 15-minute lockout (dual-key throttle on email + IP).
- **DashboardController** — Customer list with search, summary stats, customer chat history viewer.
- **AIAgentController** — View/update AI agent persona (name, system prompt, welcome message, etc.).
- **ChatAgentController** — Chat agent CRUD with duplication support.
- **ToolController** — Full CRUD for tools (info/get/update types), includes endpoint tester.
- **DataModelController** — Full CRUD for data model field schemas with required/value support.
- **DatabaseConnectionController** — Database connection CRUD with connection test.
- **ForbiddenBehaviourController** — Full CRUD for forbidden behaviour rules (scoped per agent).
- **SettingController** — Grouped global settings editor (API keys, bot tokens, support URLs, etc.).
- **MetricsController** — Observability dashboard (throughput, latency, OpenAI cost, tool execution, outbound HTTP stats).
- **LocaleController** — Language toggle (Bahasa Indonesia / English).

## Services

### AIService

Core AI orchestration service.

- `reply($message, $chatId, ...)` — Main entry point. Builds system prompt, loads conversation history, calls OpenAI, handles tool calls, returns final reply.
- `collectDebouncedMessage($chatId, $message)` — Debounces rapid messages from same chat before AI processing.
- Tool types: **info** (static text), **get** (DataModel DB lookup), **update** (HTTP endpoint call).
- Model: `gpt-4.1-mini`
- History: 20 messages, 12h TTL.
- Debounce: 2 seconds.

### Agent Services (`app/Services/Agent/`)

- **CustomerIdentityService** — Resolves unique customer identity per platform. Learns customer name from message patterns.
- **ConversationMemoryService** — Stores/fetches short-term conversation memory. Messages stored per day (unique constraint: customer_id + conversation_date). **Message cap: 50 messages per conversation per day** — older messages are trimmed on write.
- **BehaviorProfilerService** — Updates intent/sentiment/frequency behavior profile per customer.
- **AgentContextService** — Builds unified AI context combining profile, behavior, and memory.
- **DataRetentionService** — Applies retention policy for stored conversations and stale customer behavior memory.

## Queue Processing (Async AI Replies)

Telegram and WhatsApp webhooks dispatch a `ProcessAiReply` queue job instead of processing AI calls synchronously. This prevents webhook timeouts during slow OpenAI calls.

### ProcessAiReply Job (`app/Jobs/ProcessAiReply.php`)

- **Queue:** `database` (default Laravel queue connection)
- **Retries:** 3 total attempts with backoff `[10, 30]` seconds
- **Channels:** `telegram`, `whatsapp` (LiveChat stays synchronous)
- **Steps:** Resolve customer → build agent context → save user message → send typing indicator → call OpenAI → stop typing → save assistant message → send reply → record metrics
- **Config reads:** ProjectSetting values (bot tokens, WAHA URLs) are read at job execution time, not serialization, to avoid stale tokens.

### Running the Queue Worker

```bash
# Development (restarts on code changes)
php artisan queue:listen --tries=1 --timeout=0

# Production (faster, no auto-restart)
php artisan queue:work --tries=3
```

The `composer dev` script starts `queue:listen` automatically alongside the dev server.

## Support Classes (`app/Support/`)

### LogSanitizer

Prevents PII and sensitive data from appearing in log output.

- `summarize(array $payload)` — Returns top-level keys, size, and event type (no values logged).
- `redactArguments(array $args)` — Replaces sensitive values with `[REDACTED]`.
- Protected fields: text, body, message, content, caption, name, email, phone, password, token, secret, api_key, user_id, chat_id, visitor_id, etc.

### ResilientHttp

Outbound HTTP wrapper with retry, exponential backoff, and circuit breaker.

| Setting          | Value                             |
| ---------------- | --------------------------------- |
| Max attempts     | 3                                 |
| Backoff          | 200ms, 500ms, 1000ms              |
| Retryable status | 408, 425, 429, 500, 502, 503, 504 |
| Circuit breaker  | Opens after 5 failures for 60s    |
| Failure TTL      | 10 minutes                        |
| Default timeout  | 10 seconds                        |

Public method: `ResilientHttp::post($service, $url, $payload, $headers, $timeoutSeconds)`

### MetricsCollector

Lightweight fire-and-forget observability. Records structured metrics to `bot_metrics` table.

| Metric type     | What it records                             |
| --------------- | ------------------------------------------- |
| `request`       | Webhook throughput + end-to-end latency     |
| `openai_call`   | OpenAI API latency, token usage, cost (USD) |
| `tool_exec`     | Tool execution latency + success/failure    |
| `outbound_http` | External API call latency + HTTP status     |

Cost estimation uses `gpt-4.1-mini` pricing: $0.0004/1K input tokens, $0.0016/1K output tokens.

## Models

| Model              | Table                | Key Fields                                                                        | Relationships            |
| ------------------ | -------------------- | --------------------------------------------------------------------------------- | ------------------------ |
| User               | users                | name, email, password                                                             | —                        |
| ChatAgent          | chat_agents          | name, system_prompt, is_active                                                    | forbiddenBehaviours      |
| Customer           | customers            | platform, platform_user_id, phone, name, tags, first/last_seen_at, total_messages | conversations, behaviors |
| Conversation       | conversations        | customer_id, channel, conversation_date, messages (JSON)                          | customer                 |
| CustomerBehavior   | customer_behaviors   | customer_id, intent, sentiment, frequency_score, last_intent_at                   | customer                 |
| Tool               | tools                | tool_name, type, parameters, endpoints, keywords                                  | dataModel                |
| DataModel          | data_models          | model_name, slug, table_name, connection_name, fields (JSON)                      | tools                    |
| DatabaseConnection | database_connections | name, driver, host, port, database, username, password                            | dataModels               |
| ForbiddenBehaviour | forbidden_behaviours | title, description, is_enabled, chat_agent_id                                     | chatAgent                |
| ProjectSetting     | project_settings     | key, value, group, label                                                          | —                        |
| BotMetric          | bot_metrics          | metric_type, channel, meta (JSON), created_at                                     | —                        |

## Tool Types

### info

Static information tools. Returns pre-configured text from `information_text` field. No parameters needed.

### get (DataModel Lookup)

Queries an external database table via DataModel schema.

- Fields defined in DataModel JSON with `type`, `required`, and optional `value`.
- Required fields with a fixed `value` are auto-injected as WHERE filters (always override AI arguments).
- Required fields without a value must be provided by the AI (user must supply data).
- Field required status is enforced: if any required field is empty after injection, returns a missing-data message.

### update (HTTP Endpoint)

Calls an external HTTP API endpoint.

- Endpoint route, body template, and expected response defined in `endpoints` JSON.
- Body template supports `$arg->fieldName` placeholders resolved from AI arguments.
- Required parameters validated before execution.

## DataModel Field Schema

Fields stored as JSON in `data_models.fields`:

```json
{
    "username": { "type": "VARCHAR", "required": true },
    "agent": { "type": "VARCHAR", "required": true, "value": "PG" },
    "balance": { "type": "DECIMAL(14,3)", "required": false }
}
```

- `type` — SQL column type (for display/documentation).
- `required` — If true, field must be present when tool queries this model. Cannot be deleted from tool parameters.
- `value` — Optional fixed value. When set on a required field, AIService auto-injects it into every query as a WHERE filter.

## Database Tables

| Table                            | Migration         |
| -------------------------------- | ----------------- |
| users                            | 0001_01_01_000000 |
| cache / cache_locks              | 0001_01_01_000001 |
| jobs / job_batches / failed_jobs | 0001_01_01_000002 |
| personal_access_tokens           | 2026_04_08_160524 |
| customers                        | 2026_04_10_000001 |
| conversations                    | 2026_04_10_000002 |
| customer_behaviors               | 2026_04_10_000004 |
| tools                            | 2026_04_10_000006 |
| forbidden_behaviours             | 2026_04_10_120317 |
| project_settings                 | 2026_04_12_000001 |
| data_models                      | 2026_04_12_000008 |
| chat_agents                      | 2026_04_15_000001 |
| database_connections             | 2026_04_15_000001 |
| bot_metrics                      | 2026_04_17_120000 |

## Seeders

| Seeder                   | Purpose                                                                                                                         |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------- |
| AdminUserSeeder          | Default admin: `admin@xonebot.local` / `admin12345`                                                                             |
| DataModelSeeder          | Players and Settings data model schemas                                                                                         |
| ToolSeeder               | Default tools: \_bot_config, resetPassword, register, checkSuspend, toStatus, game_gacor, pola_gacor, bonus, link_rtp, link_apk |
| ForbiddenBehaviourSeeder | Banned behavior rules                                                                                                           |
| ProjectSettingSeeder     | Global settings (API keys, bot tokens, support URLs, agent config)                                                              |

## Environment Variables

```env
# OpenAI
OPENAI_API_KEY=

# Telegram
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=

# WhatsApp (WAHA)
WAHA_BASE_URL=
WAHA_SESSION=default
WAHA_API_KEY=
WAHA_WEBHOOK_SECRET=
WHATSAPP_AGENT=PG

# LiveChat
LIVECHAT_VERIFY_TOKEN=
LIVECHAT_WEBHOOK_SECRET=

# Queue
QUEUE_CONNECTION=database

# Data Retention
CONVERSATION_RETENTION_DAYS=90
CUSTOMER_MEMORY_RETENTION_DAYS=90

# Support
SUPPORT_PHONE=08120000000
SUPPORT_TELEGRAM_URL=
SUPPORT_WHATSAPP_URL=
```

All settings can also be overridden from the backoffice Settings page (stored in `project_settings` table, takes priority over `.env`).

## Data Retention

- `retention:prune` runs daily at `03:00` through the Laravel scheduler.
- Conversation history older than `CONVERSATION_RETENTION_DAYS` / `conversation_retention_days` is deleted.
- Stale customer memory in `customer_behaviors` older than `CUSTOMER_MEMORY_RETENTION_DAYS` / `customer_memory_retention_days` is deleted.
- Manual run: `php artisan retention:prune`
- Dry run: `php artisan retention:prune --dry-run`
- Server cron still needs to call `php artisan schedule:run` every minute.

## Error Handling

Custom exception renderer in `bootstrap/app.php`:

- API routes (`api/*`) always return structured JSON errors — never HTML.
- Status-based messages: 404 → "Not found.", 429 → "Too many requests.", 403 → "Forbidden.", 500+ → "Internal server error."
- Debug details (exception class, message, trace) only exposed when `APP_DEBUG=true`.
- Non-API routes fall through to Laravel's default HTML error pages.

## Observability

### Metrics Dashboard

Accessible at `/backoffice/metrics` (requires auth). Supports `?range=today|7d|30d`.

Displays:

- **Throughput** — Request count per channel
- **Average latency** — Per channel from request metrics
- **OpenAI costs** — Total tokens, cost (USD), call count, breakdown by purpose
- **Tool executions** — Per tool: total calls, failures, failure rate %, avg latency
- **Outbound HTTP** — Per service: total calls, failures, avg latency
- **Timeline** — Hourly throughput chart grouped by channel (Asia/Jakarta timezone)

### Metric Storage

All metrics stored in `bot_metrics` table via `MetricsCollector`. Fire-and-forget — collection failures are logged but never bubble up to the user.

## Project Rules

### Security

- Never hardcode secrets. Use `.env` + `config(...)`.
- Rotate leaked secrets immediately.
- Webhook endpoints are protected by channel-specific middleware (`verify.telegram`, `verify.whatsapp`, `verify.livechat`).
- Login is rate-limited: 5 failed attempts → 15-minute lockout.
- All logged payloads pass through `LogSanitizer` to redact PII.

### Configuration

- Third-party credentials in `config/services.php`.
- Read config via `config(...)`, not `env(...)` in business logic.
- After config changes: `php artisan config:clear && php artisan optimize:clear`

### Bot Behavior

- Assistant identity: xoneBot. Default language: Bahasa Indonesia.
- Keep replies short unless user requests detail.
- Conversation context per chat ID (20 messages, 12h TTL).
- Rapid messages debounced (2s) before AI processing.
- If uncertain, offer handover to human support.
- Required DataModel fields with fixed values are always auto-injected into queries.

### Code Quality

- PSR-4 naming. Keep controllers thin, logic in services.
- Log webhook receive and invalid payloads.
- Handle OpenAI errors gracefully with user-friendly messages.
- No dead/commented-out code.

### Deployment

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

Production `.env` must have `APP_DEBUG=false` and `LOG_LEVEL=warning` (or `error`).

### Static Analysis

PHPStan is configured at **level 5** with Larastan (`phpstan.neon`).

```bash
composer analyse
# or directly:
vendor/bin/phpstan analyse --memory-limit=512M
```

### Documentation

- Update `PROJECT_GUIDE.md` when behavior, structure, or rules change.

## Test Coverage

### Webhook Tests (`tests/Feature/Webhooks/`)

| Test File           | What it verifies                                        |
| ------------------- | ------------------------------------------------------- |
| TelegramWebhookTest | Job dispatch (`Queue::assertPushed`), invalid payload   |
| WhatsAppWebhookTest | Job dispatch, GET health check                          |
| LiveChatWebhookTest | Token validation, synchronous AI reply in response body |

### Backoffice Tests (`tests/Feature/Backoffice/`)

| Test File                | What it verifies                              |
| ------------------------ | --------------------------------------------- |
| BackofficeAccessTest     | Guest redirect to login, authenticated access |
| AuthLoginThrottleTest    | 5-attempt lockout, counter reset on success   |
| ToolEndpointContractTest | Tool endpoint validation contract             |

### Console Tests (`tests/Feature/Console/`)

| Test File                | What it verifies                                |
| ------------------------ | ----------------------------------------------- |
| DataRetentionCommandTest | Retention pruning for conversations & behaviors |

### Run Tests

```bash
# All tests
php artisan test

# By suite
php artisan test tests/Feature/Webhooks
php artisan test tests/Feature/Backoffice
php artisan test tests/Feature/Console
```

Notes:

- Tests isolate `project_settings` and cache state to avoid cross-test leakage.
- Backoffice POST tests disable CSRF middleware to validate auth and controller behavior independently.
- Webhook tests use `Queue::fake()` to assert job dispatch without actually running the AI.
- Backoffice access is auth-based (guest redirected to login). Role-based admin authorization is not yet implemented.
