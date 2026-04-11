<?php

namespace App\Services\Agent;

use App\Models\AILearnedMemory;
use App\Models\KnowledgeBase;
use Illuminate\Support\Collection;

class KnowledgeBaseService
{
    /**
     * Search relevant knowledge documents by extracting keywords from user message.
     * Only returns matching documents — not all data.
     */
    public function searchRelevant(string $message, int $limit = 5): Collection
    {
        $keywords = $this->extractKeywords($message);

        if ($keywords === []) {
            return collect();
        }

        return KnowledgeBase::query()
            ->where('is_active', true)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('content', 'like', '%' . $keyword . '%')
                      ->orWhere('title', 'like', '%' . $keyword . '%')
                      ->orWhere('category', 'like', '%' . $keyword . '%');
                }
            })
            ->orderByDesc('confidence_score')
            ->limit($limit)
            ->get();
    }

    public function searchLearnedMemories(string $message, int $limit = 3): Collection
    {
        $keywords = $this->extractKeywords($message);

        if ($keywords === []) {
            return collect();
        }

        return AILearnedMemory::query()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('pattern', 'like', '%' . $keyword . '%');
                }
            })
            ->orderByDesc('confidence')
            ->limit($limit)
            ->get();
    }

    public function storeIfUseful(string $title, string $content, string $category = 'general', float $confidence = 0.5): KnowledgeBase
    {
        return KnowledgeBase::query()->create([
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'confidence_score' => $confidence,
            'source' => 'auto',
            'is_active' => true,
        ]);
    }

    /**
     * Store a learned pattern from conversation (pending approval).
     */
    public function storeLearnedMemory(string $pattern, string $response, string $category = 'general'): AILearnedMemory
    {
        $existing = AILearnedMemory::query()
            ->where('pattern', $pattern)
            ->first();

        if ($existing !== null) {
            $existing->increment('hit_count');
            return $existing;
        }

        return AILearnedMemory::create([
            'pattern' => $pattern,
            'learned_response' => $response,
            'category' => $category,
            'hit_count' => 1,
            'confidence' => 0.50,
            'is_approved' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Build compact JSON snippet grouped by category.
     * Keeps it short — only matching content, sorted by category.
     */
    public function toPromptSnippet(string $message, int $limit = 5): string
    {
        $rows = $this->searchRelevant($message, $limit);
        $memories = $this->searchLearnedMemories($message, 3);

        if ($rows->isEmpty() && $memories->isEmpty()) {
            return '';
        }

        $grouped = [];

        foreach ($rows as $row) {
            $cat = $row->category ?: 'general';
            $grouped[$cat][] = $row->title
                ? "{$row->title}: {$row->content}"
                : $row->content;
        }

        foreach ($memories as $mem) {
            $cat = $mem->category ?: 'learned';
            $grouped[$cat][] = "{$mem->pattern}: {$mem->learned_response}";
        }

        ksort($grouped);

        return json_encode($grouped, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Extract meaningful keywords from user message for search.
     * Strips common Indonesian/English stop words.
     */
    private function extractKeywords(string $message): array
    {
        $message = mb_strtolower(trim($message));
        if ($message === '') {
            return [];
        }

        $stopWords = [
            'saya', 'aku', 'kamu', 'dia', 'apa', 'ini', 'itu', 'yang', 'di', 'ke', 'dari',
            'dan', 'atau', 'tapi', 'juga', 'sudah', 'belum', 'bisa', 'tidak', 'mau', 'ada',
            'dengan', 'untuk', 'akan', 'pada', 'dalam', 'lagi', 'dong', 'ya', 'nih', 'gak',
            'ga', 'gimana', 'bagaimana', 'tolong', 'mohon', 'bro', 'bang', 'min', 'kak',
            'the', 'is', 'at', 'in', 'on', 'a', 'an', 'to', 'for', 'of', 'and', 'or',
            'how', 'what', 'why', 'can', 'do', 'my', 'i', 'me', 'it', 'this', 'that',
        ];

        // Split into words, filter stop words and short words
        $words = preg_split('/[\s\?\!\.\,\:\;]+/', $message, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $keywords = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) >= 3 && !in_array($word, $stopWords, true)) {
                $keywords[] = $word;
            }
        }

        return array_values(array_unique($keywords));
    }
}
