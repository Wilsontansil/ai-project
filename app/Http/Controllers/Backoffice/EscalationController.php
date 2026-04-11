<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\EscalationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscalationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'open');

        $query = EscalationNotification::query()
            ->with('customer')
            ->latest();

        if ($filter === 'open') {
            $query->whereNull('resolved_at');
        } elseif ($filter === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        $notifications = $query->paginate(20)->withQueryString();

        $counts = [
            'open' => EscalationNotification::query()->whereNull('resolved_at')->count(),
            'unread' => EscalationNotification::query()->where('is_read', false)->count(),
            'total' => EscalationNotification::query()->count(),
        ];

        return view('backoffice.escalations.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'counts' => $counts,
            'boActive' => 'escalations',
        ]);
    }

    public function markRead(EscalationNotification $escalation): RedirectResponse
    {
        $escalation->update(['is_read' => true]);

        return redirect()->back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function resolve(EscalationNotification $escalation): RedirectResponse
    {
        $escalation->update([
            'is_read' => true,
            'resolved_at' => now(),
        ]);

        // Also clear the customer's escalation flag if no other open escalations
        $openCount = EscalationNotification::query()
            ->where('customer_id', $escalation->customer_id)
            ->whereNull('resolved_at')
            ->count();

        if ($openCount === 0) {
            Customer::query()->where('id', $escalation->customer_id)->update([
                'needs_human' => false,
                'resolved_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Eskalasi berhasil diselesaikan.');
    }

    public function markAllRead(): RedirectResponse
    {
        EscalationNotification::query()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return redirect()->back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Return unread count as JSON (for AJAX polling).
     */
    public function unreadCount(): \Illuminate\Http\JsonResponse
    {
        $count = EscalationNotification::query()
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
