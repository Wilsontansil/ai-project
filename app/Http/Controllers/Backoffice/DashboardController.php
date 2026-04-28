<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\ProjectSetting;
use App\Services\Agent\ChatAttachmentStorageService;
use App\Services\Agent\ConversationMemoryService;
use App\Services\AI\ConversationHistory;
use App\Support\ResilientHttp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
            ->with(['assignedUser:id,name,username'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%')
                        ->orWhere('platform_user_id', 'like', '%' . $search . '%')
                        ->orWhere('platform', 'like', '%' . $search . '%');
                });
            })
            ->latest('last_seen_at')
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.dashboard', [
            'customers' => $customers,
            'search' => $search,
            'stats' => [
                'total_customers' => Customer::query()->count(),
                'telegram_customers' => Customer::query()->where('platform', 'telegram')->count(),
                'whatsapp_customers' => Customer::query()->where('platform', 'whatsapp')->count(),
                'livechat_customers' => Customer::query()->where('platform', 'livechat')->count(),
            ],
        ]);
    }

    public function escalationQueue(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
            ->with(['assignedUser:id,name,username'])
            ->whereIn('mode', ['waiting', 'human'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%')
                        ->orWhere('platform_user_id', 'like', '%' . $search . '%')
                        ->orWhere('platform', 'like', '%' . $search . '%');
                });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.escalation-queue', [
            'customers' => $customers,
            'search' => $search,
            'stats' => [
                'waiting' => Customer::query()->where('mode', 'waiting')->count(),
                'human' => Customer::query()->where('mode', 'human')->count(),
            ],
        ]);
    }

    public function escalationCount(): JsonResponse
    {
        return response()->json([
            'count' => Customer::query()->where('mode', 'waiting')->count(),
        ]);
    }

    /**
     * Redirect to the public HTTP URL of a chat attachment stored on the SFTP disk.
     * The `path` query parameter must start with `chat-attachments/`.
     */
    public function chatAttachment(Request $request): RedirectResponse
    {
        $path = (string) $request->query('path', '');

        // Prevent path traversal; only allow our own attachment directory.
        if (!str_starts_with($path, 'chat-attachments/') || str_contains($path, '..')) {
            abort(403);
        }

        // Build the public HTTP URL from the disk's url config key.
        // e.g. https://devasset.pilartestengine.com/assets/aiproject/chat-attachments/...
        $baseUrl = rtrim((string) config('filesystems.disks.sftp.url', ''), '/');

        if ($baseUrl === '') {
            abort(500, 'Asset HTTP base URL is not configured.');
        }

        return redirect($baseUrl . '/' . ltrim($path, '/'));
    }

    public function takeover(Customer $customer): RedirectResponse
    {
        $user = request()->user();

        $result = DB::transaction(function () use ($customer, $user) {
            $lockedCustomer = Customer::query()
                ->with(['assignedUser:id,name,username'])
                ->lockForUpdate()
                ->findOrFail($customer->id);

            if ($lockedCustomer->assigned_user_id !== null && $lockedCustomer->assigned_user_id !== $user->id) {
                return [
                    'blocked' => true,
                    'owner_name' => $lockedCustomer->assignedUser?->name
                        ?: $lockedCustomer->assignedUser?->username
                        ?: __('backoffice.pages.customer_chat.another_user'),
                ];
            }

            $lockedCustomer->update([
                'mode' => 'human',
                'assigned_user_id' => $user->id,
                'assigned_at' => now(),
            ]);

            return ['blocked' => false];
        });

        if (($result['blocked'] ?? false) === true) {
            return back()->with('error', __('backoffice.pages.customer_chat.assign_blocked', ['name' => $result['owner_name']]));
        }

        return back()->with('success', __('backoffice.pages.escalation.takeover_success'));
    }

    public function releaseToBot(Customer $customer): RedirectResponse
    {
        $user = request()->user();

        $result = DB::transaction(function () use ($customer, $user) {
            $lockedCustomer = Customer::query()
                ->with(['assignedUser:id,name,username'])
                ->lockForUpdate()
                ->findOrFail($customer->id);

            if ($lockedCustomer->assigned_user_id !== null && $lockedCustomer->assigned_user_id !== $user->id) {
                return [
                    'blocked' => true,
                    'owner_name' => $lockedCustomer->assignedUser?->name
                        ?: $lockedCustomer->assignedUser?->username
                        ?: __('backoffice.pages.customer_chat.another_user'),
                ];
            }

            $lockedCustomer->update([
                'mode' => 'bot',
                'assigned_user_id' => null,
                'assigned_at' => null,
                'escalation_summary' => null,
            ]);

            return ['blocked' => false];
        });

        if (($result['blocked'] ?? false) === true) {
            return back()->with('error', __('backoffice.pages.customer_chat.assign_blocked', ['name' => $result['owner_name']]));
        }

        // Clear AI conversation history so the customer can escalate again in a fresh session.
        app(ConversationHistory::class)->clear($customer->platform_user_id, $customer->platform);

        // Clear any stale pending-tool / chain-carry state so the bot resumes cleanly.
        $cachePrefix = $customer->platform !== '' ? $customer->platform . ':' : '';
        Cache::forget("pending_tool:{$cachePrefix}{$customer->platform_user_id}");
        Cache::forget("chain_carry:{$cachePrefix}{$customer->platform_user_id}");

        return back()->with('success', __('backoffice.pages.escalation.release_success'));
    }

    public function chat(Request $request, Customer $customer): View
    {
        $customer->loadMissing(['assignedUser:id,name,username']);

        $startDate = $request->query('start_date', now()->toDateString());
        $endDate   = $request->query('end_date', now()->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $startDate) || !strtotime((string) $startDate)) {
            $startDate = now()->toDateString();
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $endDate) || !strtotime((string) $endDate)) {
            $endDate = now()->toDateString();
        }

        $conversations = Conversation::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('conversation_date', [$startDate, $endDate])
            ->orderBy('conversation_date')
            ->get();

        // Combine messages from all dates into a single flat array
        $messages = [];
        foreach ($conversations as $convo) {
            $date = $convo->conversation_date->toDateString();
            foreach ($convo->messages ?? [] as $msg) {
                $msg['date'] = $date;
                $messages[] = $msg;
            }
        }

        return view('backoffice.customer-chat', [
            'customer' => $customer,
            'messages' => $messages,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function messages(Request $request, Customer $customer): JsonResponse
    {
        $customer->loadMissing(['assignedUser:id,name,username']);

        $startDate = $request->query('start_date', now()->toDateString());
        $endDate   = $request->query('end_date', now()->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $startDate) || !strtotime((string) $startDate)) {
            $startDate = now()->toDateString();
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $endDate) || !strtotime((string) $endDate)) {
            $endDate = now()->toDateString();
        }

        $conversations = Conversation::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('conversation_date', [$startDate, $endDate])
            ->orderBy('conversation_date')
            ->get();

        $messages = [];
        foreach ($conversations as $convo) {
            $date = $convo->conversation_date->toDateString();
            foreach ($convo->messages ?? [] as $msg) {
                $msg['date'] = $date;
                $messages[] = $msg;
            }
        }

        $isOwner = $customer->assigned_user_id !== null
            && (int) $customer->assigned_user_id === (int) optional($request->user())->id;

        return response()->json([
            'messages'     => $messages,
            'customer_mode' => $customer->mode,
            'can_send'     => $customer->mode === 'human' && $isOwner && in_array($customer->platform, ['telegram', 'whatsapp', 'livechat']),
            'assigned_user_id' => $customer->assigned_user_id,
            'assigned_user_name' => $customer->assignedUser?->name ?: $customer->assignedUser?->username,
        ]);
    }

    public function sendMessage(Request $request, Customer $customer): RedirectResponse
    {
        $currentUser = $request->user();
        $customer->loadMissing(['assignedUser:id,name,username']);

        if ($customer->mode !== 'human') {
            return back()->with('send_error', __('backoffice.pages.customer_chat.send_error_not_human'));
        }

        if ((int) $customer->assigned_user_id !== (int) $currentUser->id) {
            $ownerName = $customer->assignedUser?->name ?: $customer->assignedUser?->username ?: __('backoffice.pages.customer_chat.another_user');
            return back()->with('send_error', __('backoffice.pages.customer_chat.send_error_not_owner', ['name' => $ownerName]));
        }

        if (!in_array($customer->platform, ['telegram', 'whatsapp', 'livechat'])) {
            return back()->with('send_error', __('backoffice.pages.customer_chat.send_error_platform'));
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string'],
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,text/csv,application/zip,application/x-zip-compressed,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,video/mp4,video/webm,audio/mpeg,audio/mp4,audio/ogg,audio/wav',
            ],
        ]);

        $message = trim((string) ($validated['message'] ?? ''));
        /** @var UploadedFile|null $attachmentFile */
        $attachmentFile = $request->file('attachment');

        if ($message === '' && $attachmentFile === null) {
            return back()->with('send_error', __('backoffice.pages.customer_chat.send_error_empty'));
        }

        $chatId = $customer->platform_user_id;

        if ($customer->platform === 'livechat') {
            $livechatChatId = ($customer->tags ?? [])['livechat_chat_id'] ?? null;
            if ($livechatChatId === null || $livechatChatId === '') {
                return back()->with('send_error', __('backoffice.pages.customer_chat.send_error_failed'));
            }
            $chatId = $livechatChatId;
        }

        try {
            $attachmentMeta = null;
            $historyMessage = $message;

            if ($attachmentFile !== null) {
                $attachmentMeta = $this->storeUploadedAttachment($customer, $attachmentFile);
                $historyMessage = $historyMessage !== ''
                    ? $historyMessage
                    : $this->syntheticAttachmentText($attachmentMeta);
            }

            if ($customer->platform === 'telegram') {
                if ($attachmentFile !== null && $attachmentMeta !== null) {
                    $this->sendTelegramAttachment($chatId, $attachmentFile, $attachmentMeta, $message);
                } else {
                    $this->sendTelegram($chatId, $message);
                }
            } elseif ($customer->platform === 'whatsapp') {
                if ($attachmentMeta !== null) {
                    $this->sendWhatsAppAttachment($chatId, $attachmentMeta, $attachmentFile, $message);
                } else {
                    $this->sendWhatsApp($chatId, $message);
                }
            } else {
                $threadId = ($customer->tags ?? [])['livechat_thread_id'] ?? null;
                if ($attachmentMeta !== null) {
                    $this->sendLiveChatAttachmentFallback($chatId, $attachmentMeta, $message, $threadId);
                } else {
                    $this->sendLiveChat($chatId, $message, $threadId);
                }
            }

            app(ConversationMemoryService::class)->addMessage(
                $customer,
                $customer->platform,
                'assistant',
                $historyMessage,
                [
                    'sent_by' => 'admin',
                    'sent_by_user_id' => $currentUser->id,
                    'sent_by_user_name' => $currentUser->name ?: $currentUser->username,
                    'attachment' => $attachmentMeta,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Admin send message failed', [
                'customer_id' => $customer->id,
                'platform' => $customer->platform,
                'error' => $e->getMessage(),
            ]);

            return back()->with('send_error', __('backoffice.pages.customer_chat.send_error_failed'));
        }

        return back()->with('send_success', __('backoffice.pages.customer_chat.send_success'));
    }

    private function storeUploadedAttachment(Customer $customer, UploadedFile $attachmentFile): array
    {
        return app(ChatAttachmentStorageService::class)->store(
            $customer->platform,
            (string) $customer->platform_user_id,
            $attachmentFile->getClientOriginalName(),
            $attachmentFile->getMimeType() ?: 'application/octet-stream',
            $attachmentFile->get()
        );
    }

    private function syntheticAttachmentText(array $attachmentMeta): string
    {
        return match ($attachmentMeta['type'] ?? 'document') {
            'image' => '[image]',
            'video' => '[video]',
            'audio' => '[audio]',
            default => '[document: ' . ($attachmentMeta['original_name'] ?? 'file') . ']',
        };
    }

    private function sendTelegramAttachment(string $chatId, UploadedFile $attachmentFile, array $attachmentMeta, string $caption = ''): void
    {
        $token = (string) ProjectSetting::getValue('telegram_bot_token', config('services.telegram.bot_token', ''));

        if ($token === '') {
            throw new \RuntimeException('Telegram bot token is not configured.');
        }

        $method = ($attachmentMeta['type'] ?? 'document') === 'image' ? 'sendPhoto' : 'sendDocument';
        $field = $method === 'sendPhoto' ? 'photo' : 'document';
        $url = "https://api.telegram.org/bot{$token}/{$method}";

        $request = Http::timeout(20)
            ->attach($field, $attachmentFile->get(), $attachmentFile->getClientOriginalName())
            ->asMultipart();

        $payload = ['chat_id' => $chatId];
        if ($caption !== '') {
            $payload['caption'] = $caption;
        }

        $response = $request->post($url, $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Telegram attachment send failed with status: ' . $response->status());
        }
    }

    private function sendTelegram(string $chatId, string $text): void
    {
        $token = (string) ProjectSetting::getValue('telegram_bot_token', config('services.telegram.bot_token', ''));

        if ($token === '') {
            throw new \RuntimeException('Telegram bot token is not configured.');
        }

        ResilientHttp::post('telegram', "https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
        ], timeoutSeconds: 10);
    }

    private function sendWhatsApp(string $chatId, string $text): void
    {
        $baseUrl = rtrim((string) ProjectSetting::getValue('whatsapp_base_url', config('services.whatsapp.base_url', '')), '/');

        if ($baseUrl === '') {
            throw new \RuntimeException('WAHA base URL is not configured.');
        }

        $session = (string) ProjectSetting::getValue('whatsapp_session', config('services.whatsapp.session', 'default'));
        $apiKey = (string) ProjectSetting::getValue('whatsapp_api_key', config('services.whatsapp.api_key', ''));

        $headers = ['Accept' => 'application/json'];
        if ($apiKey !== '') {
            $headers['X-Api-Key'] = $apiKey;
        }

        $response = ResilientHttp::post('waha', $baseUrl . '/api/sendText', [
            'session' => $session,
            'chatId' => $chatId,
            'text' => $text,
        ], $headers, timeoutSeconds: 10);

        if ($response !== null && $response->failed()) {
            throw new \RuntimeException('WAHA sendText failed with status: ' . $response->status());
        }
    }

    private function sendWhatsAppAttachment(string $chatId, array $attachmentMeta, UploadedFile $attachmentFile, string $caption = ''): void
    {
        $baseUrl = rtrim((string) ProjectSetting::getValue('whatsapp_base_url', config('services.whatsapp.base_url', '')), '/');

        if ($baseUrl === '') {
            throw new \RuntimeException('WAHA base URL is not configured.');
        }

        $session = (string) ProjectSetting::getValue('whatsapp_session', config('services.whatsapp.session', 'default'));
        $apiKey = (string) ProjectSetting::getValue('whatsapp_api_key', config('services.whatsapp.api_key', ''));
        $headers = ['Accept' => 'application/json'];
        if ($apiKey !== '') {
            $headers['X-Api-Key'] = $apiKey;
        }

        $fileUrl = $this->publicAttachmentUrl((string) ($attachmentMeta['path'] ?? ''));
        $fileName = (string) ($attachmentMeta['original_name'] ?? 'file');
        $mimeType = (string) ($attachmentMeta['mime_type'] ?? 'application/octet-stream');
        $fileData = base64_encode($attachmentFile->get());
        $isImage = ($attachmentMeta['type'] ?? '') === 'image';

        $attempts = [];
        $payloadVariants = $isImage
            ? [
                ['/api/sendImage', [
                    'session' => $session,
                    'chatId' => $chatId,
                    'file' => [
                        'data' => $fileData,
                        'filename' => $fileName,
                        'mimetype' => $mimeType,
                    ],
                    'caption' => $caption,
                ]],
                ['/api/sendImage', [
                    'session' => $session,
                    'chatId' => $chatId,
                    'file' => [
                        'url' => $fileUrl,
                        'filename' => $fileName,
                        'mimetype' => $mimeType,
                    ],
                    'caption' => $caption,
                ]],
                ['/api/sendFile', [
                    'session' => $session,
                    'chatId' => $chatId,
                    'file' => [
                        'data' => $fileData,
                        'filename' => $fileName,
                        'mimetype' => $mimeType,
                    ],
                    'caption' => $caption,
                ]],
                ['/api/sendFile', [
                    'session' => $session,
                    'chatId' => $chatId,
                    'file' => [
                        'url' => $fileUrl,
                        'filename' => $fileName,
                        'mimetype' => $mimeType,
                    ],
                    'caption' => $caption,
                ]],
            ]
            : [
                ['/api/sendFile', [
                    'session' => $session,
                    'chatId' => $chatId,
                    'file' => [
                        'data' => $fileData,
                        'filename' => $fileName,
                        'mimetype' => $mimeType,
                    ],
                    'caption' => $caption,
                ]],
                ['/api/sendFile', [
                    'session' => $session,
                    'chatId' => $chatId,
                    'file' => [
                        'url' => $fileUrl,
                        'filename' => $fileName,
                        'mimetype' => $mimeType,
                    ],
                    'caption' => $caption,
                ]],
            ];

        foreach ($payloadVariants as [$endpoint, $payload]) {
            $response = ResilientHttp::post('waha', $baseUrl . $endpoint, $payload, $headers, timeoutSeconds: 20);

            $attempts[] = [
                'endpoint' => $endpoint,
                'status' => $response?->status(),
                'ok' => $response !== null && $response->successful(),
                'body' => $response !== null ? mb_substr((string) $response->body(), 0, 500) : null,
            ];

            if ($response !== null && $response->successful()) {
                return;
            }
        }

        Log::error('WAHA attachment send failed across all endpoint variants', [
            'chat_id' => $chatId,
            'path' => $attachmentMeta['path'] ?? null,
            'attempts' => $attempts,
        ]);

        throw new \RuntimeException('WAHA attachment send failed for all endpoint variants.');
    }

    private function sendLiveChat(string $chatId, string $text, ?string $threadId = null): void
    {
        $basicToken = (string) ProjectSetting::getValue('livechat_basic_token', config('services.livechat.basic_token', ''));

        if ($basicToken === '') {
            throw new \RuntimeException('LiveChat basic token is not configured.');
        }

        $headers = [
            'Authorization' => 'Basic ' . $basicToken,
            'Content-Type'  => 'application/json',
            'X-Region'      => 'us-south1',
        ];

        // Ensure the agent is a member of the chat before sending.
        // The send_event API returns 422 if the agent is not a chat member.
        $agentId = (string) ProjectSetting::getValue('livechat_agent_id', config('services.livechat.agent_id', ''));
        if ($agentId !== '') {
            ResilientHttp::post(
                'livechat',
                'https://api.livechatinc.com/v3.6/agent/action/add_user_to_chat',
                [
                    'chat_id'   => $chatId,
                    'user_id'   => $agentId,
                    'user_type' => 'agent',
                ],
                $headers,
                timeoutSeconds: 10
            );
            // Ignore response — agent may already be a member, which is fine.
        }

        $eventPayload = [
            'chat_id' => $chatId,
            'event'   => [
                'type'       => 'message',
                'text'       => $text,
                'visibility' => 'all',
            ],
        ];

        if ($threadId !== null && $threadId !== '') {
            $eventPayload['thread_id'] = $threadId;
        }

        $response = ResilientHttp::post(
            'livechat',
            'https://api.livechatinc.com/v3.6/agent/action/send_event',
            $eventPayload,
            $headers,
            timeoutSeconds: 10
        );

        if ($response !== null && $response->failed()) {
            Log::error('LiveChat send_event failed', [
                'chat_id'   => $chatId,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);
            throw new \RuntimeException('LiveChat send_event failed with status: ' . $response->status());
        }
    }

    private function sendLiveChatAttachmentFallback(string $chatId, array $attachmentMeta, string $caption = '', ?string $threadId = null): void
    {
        $message = $this->buildAttachmentFallbackMessage($attachmentMeta, $caption);
        $this->sendLiveChat($chatId, $message, $threadId);
    }

    private function buildAttachmentFallbackMessage(array $attachmentMeta, string $caption = ''): string
    {
        $attachmentUrl = $this->publicAttachmentUrl((string) ($attachmentMeta['path'] ?? ''));
        $label = $caption !== ''
            ? $caption
            : $this->syntheticAttachmentText($attachmentMeta);

        return trim($label . "\n" . $attachmentUrl);
    }

    private function publicAttachmentUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('filesystems.disks.sftp.url', ''), '/');

        if ($baseUrl === '' || $path === '') {
            throw new \RuntimeException('Attachment public base URL is not configured.');
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }
}
