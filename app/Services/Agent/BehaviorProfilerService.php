<?php

namespace App\Services\Agent;

use App\Models\Customer;
use App\Models\CustomerBehavior;

class BehaviorProfilerService
{
    public function updateFromMessage(Customer $customer, string $message): CustomerBehavior
    {
        $intent = $this->detectIntent($message);
        $sentiment = $this->detectSentiment($message);

        $behavior = CustomerBehavior::query()->firstOrNew([
            'customer_id' => $customer->id,
        ]);

        $behavior->intent = $intent;
        $behavior->sentiment = $sentiment;
        $behavior->frequency_score = (float) $behavior->frequency_score + 1;
        $behavior->last_intent_at = now();
        $behavior->extra = array_merge((array) $behavior->extra, [
            'last_message_preview' => mb_substr($message, 0, 120),
        ]);
        $behavior->save();

        return $behavior;
    }

    private function detectIntent(string $message): string
    {
        $text = mb_strtolower($message);

        if (str_contains($text, 'deposit')) {
            return 'deposit';
        }
        if (str_contains($text, 'withdraw') || str_contains($text, 'wd')) {
            return 'withdraw';
        }
        if (str_contains($text, 'bonus')) {
            return 'ask_bonus';
        }
        if (str_contains($text, 'suspend')) {
            return 'check_suspend';
        }
        if (str_contains($text, 'reset') && str_contains($text, 'password')) {
            return 'reset_password';
        }
        if (str_contains($text, 'register') || str_contains($text, 'daftar') || str_contains($text, 'registrasi')) {
            return 'register';
        }

        return 'general_question';
    }

    private function detectSentiment(string $message): string
    {
        $text = mb_strtolower($message);

        $negativeMarkers = ['marah', 'kecewa', 'lemot', 'jelek', 'bad', 'error', 'gagal'];
        foreach ($negativeMarkers as $marker) {
            if (str_contains($text, $marker)) {
                return 'negative';
            }
        }

        $positiveMarkers = ['makasih', 'terima kasih', 'bagus', 'mantap', 'good'];
        foreach ($positiveMarkers as $marker) {
            if (str_contains($text, $marker)) {
                return 'positive';
            }
        }

        return 'neutral';
    }
}
