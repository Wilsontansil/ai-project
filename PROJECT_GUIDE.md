# AI Project Guide

## Project Overview

Laravel 13 API that connects Telegram, WhatsApp (WAHA), and LiveChat messages to an OpenAI-powered assistant (xoneBot). Includes a full backoffice for managing tools, data models, forbidden behaviours, and global settings.

## Architecture

```
Telegram/WhatsApp/LiveChat → Webhook Controller → AIService → OpenAI
                                    ↓
                            Agent Context (identity, memory, behavior)
                                    ↓
                            Tool Execution (info / get / update)
                                    ↓
                            Reply → Telegram/WhatsApp/LiveChat API
```

## API Endpoints

| Method   | Path                    | Controller         | Purpose               |
| -------- | ----------------------- | ------------------ | --------------------- |
| GET      | `/api/test`             | closure            | Health check          |
| POST     | `/api/telegram/webhook` | TelegramController | Telegram webhook      |
| GET/POST | `/api/whatsapp/webhook` | WhatsAppController | WAHA WhatsApp webhook |
| GET/POST | `/api/livechat/webhook` | LiveChatController | LiveChat webhook      |

## Backoffice Routes

All backoffice routes require authentication (`auth` middleware).

| Path                               | Controller                   | Purpose                        |
| ---------------------------------- | ---------------------------- | ------------------------------ |
| `/backoffice/login`                | AuthController               | Admin login                    |
| `/backoffice`                      | DashboardController          | Customer dashboard + stats     |
| `/backoffice/customer/{id}/chat`   | DashboardController          | Chat history view              |
| `/backoffice/ai-agent`             | AIAgentController            | Agent persona settings         |
| `/backoffice/tools`                | ToolController               | CRUD for AI tools              |
| `/backoffice/tools/test-endpoint`  | ToolController               | Test tool HTTP endpoint        |
| `/backoffice/data-models`          | DataModelController          | CRUD for data models           |
| `/backoffice/forbidden-behaviours` | ForbiddenBehaviourController | CRUD for banned behavior rules |
| `/backoffice/settings`             | SettingController            | Global project settings        |

## Controllers

### Webhook Controllers

- **TelegramController** — Validates Telegram payload, debounces rapid messages, calls AIService, sends reply via Telegram Bot API.
- **WhatsAppController** — Accepts WAHA webhook payloads, sends typing indicator, calls AIService, sends reply via WAHA `sendText` API.
- **LiveChatController** — Handles LiveChat webhook payloads, calls AIService, responds.

### Backoffice Controllers

- **AuthController** — Login/logout with session auth.
- **DashboardController** — Customer list with search, summary stats, customer chat history viewer.
- **AIAgentController** — View/update AI agent persona (name, system prompt, welcome message, etc.).
- **ToolController** — Full CRUD for tools (info/get/update types), includes endpoint tester.
- **DataModelController** — Full CRUD for data model field schemas with required/value support.
- **ForbiddenBehaviourController** — Full CRUD for forbidden behaviour rules.
- **SettingController** — Grouped global settings editor (API keys, bot tokens, support URLs, etc.).

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
- **ConversationMemoryService** — Stores/fetches short-term conversation memory.
- **BehaviorProfilerService** — Updates intent/sentiment/frequency behavior profile per customer.
- **AgentContextService** — Builds unified AI context combining profile, behavior, and memory.

## Models

| Model              | Table                | Key Fields                                                   | Relationships            |
| ------------------ | -------------------- | ------------------------------------------------------------ | ------------------------ |
| User               | users                | name, email, password                                        | —                        |
| Agent              | agents               | name, system_prompt, is_active                               | —                        |
| Customer           | customers            | platform, platform_id, display_name                          | conversations, behaviors |
| Conversation       | conversations        | customer_id, role, content                                   | customer                 |
| CustomerBehavior   | customer_behaviors   | customer_id, key, value                                      | customer                 |
| Tool               | tools                | tool_name, type, parameters, endpoints, keywords             | dataModel                |
| DataModel          | data_models          | model_name, slug, table_name, connection_name, fields (JSON) | tools                    |
| ForbiddenBehaviour | forbidden_behaviours | title, description, is_enabled                               | —                        |
| ProjectSetting     | project_settings     | key, value, group, label                                     | —                        |

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

# WhatsApp (WAHA)
WAHA_BASE_URL=
WAHA_SESSION=default
WAHA_API_KEY=

# Agent
AGENT_ID=1
AGENT_KODE=PG

# Support
SUPPORT_PHONE=08120000000
SUPPORT_TELEGRAM_URL=
SUPPORT_WHATSAPP_URL=
```

All settings can also be overridden from the backoffice Settings page (stored in `project_settings` table, takes priority over `.env`).

## Project Rules

### Security

- Never hardcode secrets. Use `.env` + `config(...)`.
- Rotate leaked secrets immediately.

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

### Documentation

- Update `PROJECT_GUIDE.md` when behavior, structure, or rules change.
