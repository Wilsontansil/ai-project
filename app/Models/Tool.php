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
        'type',
        'is_enabled',
        'data_model_id',
        'parameters',
        'endpoints',
        'keywords',
        'tool_rules',
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
     * All enabled tools get a definition so OpenAI is aware of them.
     */
    public function getDefinition(): ?array
    {
        $params = $this->parameters;

        // Use empty parameters for info-only tools so OpenAI still sees them.
        if (empty($params) || empty($params['properties'])) {
            $params = ['type' => 'object', 'properties' => (object) []];
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
     * Whether this tool requires user-supplied arguments (has non-empty parameters).
     */
    public function needsArguments(): bool
    {
        $params = $this->parameters;

        return !empty($params) && !empty($params['properties']);
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

    public function dataModel(): BelongsTo
    {
        return $this->belongsTo(DataModel::class);
    }
}
