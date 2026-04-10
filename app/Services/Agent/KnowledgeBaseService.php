<?php

namespace App\Services\Agent;

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
            ->where('question', 'like', '%' . $query . '%')
            ->orWhere('answer', 'like', '%' . $query . '%')
            ->orderByDesc('confidence_score')
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
        ]);
    }

    public function toPromptSnippet(string $message, int $limit = 5): string
    {
        $rows = $this->searchRelevant($message, $limit);

        if ($rows->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = '- Q: ' . $row->question;
            $lines[] = '  A: ' . $row->answer;
        }

        return implode("\n", $lines);
    }
}
