<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
            ],
        ]);
    }
}
