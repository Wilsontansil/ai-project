<?php

namespace App\Models;

use App\Models\DataModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_name',
        'display_name',
        'description',
        'slug',
        'is_enabled',
        'data_model_id',
        'parameters',
        'endpoints',
        'keywords',
        'missing_message',
        'information_text',
        'meta',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'parameters' => 'array',
        'endpoints' => 'array',
        'keywords' => 'array',
        'information_text' => 'array',
        'meta' => 'array',
    ];

    /**
     * Build the OpenAI tool definition from DB columns.
     * Returns null if tool has no parameters (info-only tool).
     */
    public function getDefinition(): ?array
    {
        $params = $this->parameters;

        // Info-only tools with no parameters don't need an OpenAI function definition.
        if (empty($params) || empty($params['properties'])) {
            return null;
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $this->tool_name,
                'description' => $this->description ?? '',
                'parameters' => $params,
            ],
        ];
    }

    /**
     * Return the best keyword match score for a user message.
     * Score = length of longest matched keyword. 0 = no match.
     */
    public function matchScore(string $message): int
    {
        $keywords = $this->keywords ?? [];
        $best = 0;

        foreach ($keywords as $keyword) {
            $kw = (string) $keyword;
            if ($kw !== '' && stripos($message, $kw) !== false) {
                $best = max($best, mb_strlen($kw));
            }
        }

        return $best;
    }

    /**
     * Get fallback message when required parameters are missing.
     */
    public function getMissingMessage(): string
    {
        return $this->missing_message ?? 'Mohon lengkapi data yang diperlukan.';
    }

    public function dataModel(): BelongsTo
    {
        return $this->belongsTo(DataModel::class);
    }
}
