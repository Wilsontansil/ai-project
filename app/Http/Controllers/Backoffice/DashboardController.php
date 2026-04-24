<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\ProjectSetting;
use App\Services\Agent\ConversationMemoryService;
use App\Support\ResilientHttp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
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

    public function takeover(Customer $customer): RedirectResponse
    {
        $customer->update(['mode' => 'human']);

        return back()->with('success', __('backoffice.pages.escalation.takeover_success'));
    }

    public function releaseToBot(Customer $customer): RedirectResponse
    {
        $customer->update(['mode' => 'bot']);

        return back()->with('success', __('backoffice.pages.escalation.release_success'));
    }

    public function chat(Request $request, Customer $customer): View
    {
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

        return response()->json([
            'messages'     => $messages,
            'customer_mode' => $customer->mode,
            'can_send'     => $customer->mode === 'human' && in_array($customer->platform, ['telegram', 'whatsapp', 'livechat']),
        ]);
    }

    public function sendMessage(Request $request, Customer $customer): RedirectResponse
    {
        if ($customer->mode !== 'human') {
            return back()->with('send_error', __('backoffice.pages.customer_chat.send_error_not_human'));
        }

        if (!in_array($customer->platform, ['telegram', 'whatsapp', 'livechat'])) {
            return back()->with('send_error', __('backoffice.pages.customer_chat.send_error_platform'));
        }

        $message = trim((string) $request->input('message', ''));

        if ($message === '') {
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
            if ($customer->platform === 'telegram') {
                $this->sendTelegram($chatId, $message);
            } elseif ($customer->platform === 'whatsapp') {
                $this->sendWhatsApp($chatId, $message);
            } else {
                $threadId = ($customer->tags ?? [])['livechat_thread_id'] ?? null;
                $this->sendLiveChat($chatId, $message, $threadId);
            }

            app(ConversationMemoryService::class)->addMessage(
                $customer,
                $customer->platform,
                'assistant',
                $message,
                ['sent_by' => 'admin']
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
}
