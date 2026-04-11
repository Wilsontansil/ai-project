<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\EscalationNotification;
use Illuminate\Http\Request;
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
                'needs_human' => Customer::query()->where('needs_human', true)->count(),
            ],
        ]);
    }

    public function chat(Request $request, Customer $customer): View
    {
        $startDate = $request->query('start_date', now()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

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
}
