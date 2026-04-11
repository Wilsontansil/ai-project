<?php

namespace App\Services\Agent;

use App\Models\AILearnedMemory;
use App\Models\KnowledgeBase;
use Illuminate\Support\Collection;

class KnowledgeBaseService
{
    public function searchRelevant(string $message, int $limit = 5): Collection
    {
        $query = trim($message);

        if ($query === '') {
            return collect();
        }

        return KnowledgeBase::query()
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('question', 'like', '%' . $query . '%')
                    ->orWhere('answer', 'like', '%' . $query . '%');
            })
            ->orderByDesc('confidence_score')
            ->limit($limit)
            ->get();
    }

    public function searchLearnedMemories(string $message, int $limit = 3): Collection
    {
        $query = trim($message);

        if ($query === '') {
            return collect();
        }

        return AILearnedMemory::query()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->where('pattern', 'like', '%' . $query . '%')
            ->orderByDesc('confidence')
            ->limit($limit)
            ->get();
    }

    public function storeIfUseful(string $question, string $answer, array $tags = [], float $confidence = 0.5): KnowledgeBase
    {
        return KnowledgeBase::query()->create([
            'question' => $question,
            'answer' => $answer,
            'tags' => $tags,
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
        // Check for existing similar pattern to increment hit_count
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

    public function toPromptSnippet(string $message, int $limit = 5): string
    {
        $rows = $this->searchRelevant($message, $limit);
        $memories = $this->searchLearnedMemories($message, 3);

        $lines = [];

        if ($rows->isNotEmpty()) {
            foreach ($rows as $row) {
                $lines[] = '- Q: ' . $row->question;
                $lines[] = '  A: ' . $row->answer;
            }
        }

        if ($memories->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Learned patterns:';
            foreach ($memories as $mem) {
                $lines[] = '- Pattern: ' . $mem->pattern;
                $lines[] = '  Response: ' . $mem->learned_response;
            }
        }

        return implode("\n", $lines);
    }
}
