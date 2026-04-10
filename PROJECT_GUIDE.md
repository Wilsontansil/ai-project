# AI Project Guide

## Project Explanation

This project is a Laravel 13 API that connects Telegram and WhatsApp messages to an OpenAI-powered assistant named xoneBot.

Main purpose:

- Receive user messages from Telegram webhook.
- Receive user messages from WAHA WhatsApp webhook.
- Send user message and conversation history to OpenAI.
- Return the AI reply back to Telegram or WhatsApp.

Current key endpoints:

- `GET /api/test`: simple API health check.
- `POST /api/telegram/webhook`: Telegram webhook receiver.
- `GET|POST /api/whatsapp/webhook`: WAHA WhatsApp webhook receiver.

Main components:

- `app/Http/Controllers/TelegramController.php`
    - Validates incoming Telegram payload.
    - Extracts `message.text` and `message.chat.id`.
    - Calls `AIService` and sends the reply to Telegram.
- `app/Services/AIService.php`
    - Builds OpenAI chat request.
    - Adds system prompt and prior conversation context.
    - Stores per-chat memory using Laravel Cache.
    - Handles function/tool call flow for password reset intent.
- `app/Services/Agent/CustomerIdentityService.php`
    - Resolves unique customer identity from each platform payload.
- `app/Services/Agent/ConversationMemoryService.php`
    - Stores and fetches short-term conversation memory.
- `app/Services/Agent/KnowledgeBaseService.php`
    - Stores and retrieves reusable long-term knowledge entries.
- `app/Services/Agent/BehaviorProfilerService.php`
    - Updates intent/sentiment/frequency behavior profile per customer.
- `app/Services/Agent/AgentContextService.php`
    - Builds unified AI context: profile + behavior + memory + knowledge.
- `app/Http/Controllers/WhatsAppController.php`
    - Accepts WAHA webhook payloads.
    - Extracts text and chat id from common WAHA message fields.
    - Sends WAHA typing indicator while waiting for AI response.
    - Calls `AIService` and sends the reply through WAHA `sendText` API.

Conversation memory behavior:

- Context is stored per chat id with cache key format: `chat_context:{chatId}`.
- Maximum stored messages: 20 (rolling window).
- Cache TTL: 12 hours.

Learning persistence tables:

- `customers`
- `conversations`
- `knowledge_base`
- `customer_behaviors`

## Message Flow

1. Telegram sends webhook to `/api/telegram/webhook`.
2. Telegram or WAHA controller reads user text and chat id.
3. Controller calls `AIService::reply($text, $chatId)`.
4. Service loads previous context from cache.
5. Service sends `system + history + current user message` to OpenAI.
6. Service saves new user/assistant turn to cache.
7. Controller sends assistant response back to Telegram.

## Environment Requirements

- PHP 8.3+
- Composer
- Laravel dependencies installed
- OpenAI API key configured in environment:
    - `OPENAI_API_KEY=...`
- WAHA WhatsApp configured in environment when using WhatsApp:
    - `WAHA_BASE_URL=...`
    - `WAHA_SESSION=...`
    - `WAHA_API_KEY=...`

Recommended cache driver for production conversation memory:

- Redis (preferred), or database cache.

## Project Rules

### 1. Security Rules

- Never hardcode secrets (OpenAI key, Telegram bot token, database credentials).
- Keep all secrets in `.env` and access via `config(...)`.
- Do not commit real tokens, keys, or passwords to git history.
- If a secret leaks, rotate it immediately.

### 2. Configuration Rules

- Define third-party credentials in `config/services.php`.
- Read configuration in code via `config(...)`, not direct `env(...)` in business logic.
- After changing env/config in server, run:
    - `php artisan config:clear`
    - `php artisan optimize:clear`

### 3. API and Bot Behavior Rules

- Always validate webhook payload before processing.
- If payload is invalid, return safe response and do not call OpenAI.
- Keep assistant identity consistent as xoneBot.
- Default assistant response language is Bahasa Indonesia, unless user asks for another language.
- Keep default reply short and to the point unless user asks for detailed explanation.
- Keep conversation context per user/chat id.
- For password reset flow, identify account by `username` and validate player by `username + agent` before any action.
- Current reset flow sets player password to `1234567` after username+agent validation.
- Reset password now requires verification fields: `username`, `namarek`, `norek`, and `bank` (matching database fields).
- If player verification fields (`namarek`, `norek`, `bank`) are nullable/empty in database, bot should direct user to human support.
- Reset request can be triggered by OpenAI tool call or fallback local intent parsing (`username: ...`) to improve reliability.
- If bot is stuck or uncertain, it should offer handover to human support using default phone `08120000000`.
- Handover link can be different per channel using `SUPPORT_TELEGRAM_URL` and `SUPPORT_WHATSAPP_URL`.
- Incoming rapid messages from same chat are debounced and can be combined before AI processing to reduce duplicate replies.

### 4. Code Quality Rules

- Use PSR-4 compatible class and file naming (example: `AIService.php`).
- Keep controllers thin and business logic in services.
- Add clear logs for webhook receive and invalid payload cases.
- Handle OpenAI errors gracefully and return user-friendly messages.

### 5. Git and Deployment Rules

- Run syntax checks before pushing:
    - `php -l app/Services/AIService.php`
    - `php -l app/Http/Controllers/TelegramController.php`
- Keep commits focused and descriptive.
- Deploy with:
    - `composer install --no-dev --optimize-autoloader`
    - `php artisan optimize:clear`

### 6. Documentation Update Rules

- On every code/config update, review `PROJECT_GUIDE.md` and update it if behavior, setup, structure, or rules changed.
- If no guide update is needed, confirm this in the PR/commit note (example: "PROJECT_GUIDE reviewed, no changes needed").

## Suggested Next Improvements

- Move Telegram bot token to `.env` and `config/services.php`.
- Add automated tests for webhook payload and service response handling.
- Add command/endpoint to reset chat memory for a specific user.
- Move function-call operations to dedicated action classes.
