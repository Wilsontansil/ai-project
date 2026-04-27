<?php

namespace App\Models;

use App\Models\DataModel;
use App\Models\ChatAgent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'category',
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
     * Negation words (Indonesian + English) used to detect when a user
     * explicitly rejects/negates a keyword. Shared across methods.
     */
    public const NEGATION_WORDS = [
        // Indonesian
        'bukan', 'tidak', 'tdk', 'gak', 'nggak', 'ngga', 'ga', 'enggak', 'jangan', 'belum', 'tak',
        // English
        'not', 'no', 'dont', "don't", 'cancel', 'batal',
    ];

    /**
     * Check whether a keyword is preceded by a negation word in the message.
     */
    public static function isNegated(string $keyword, string $message): bool
    {
        $negations = implode('|', array_map(fn ($w) => preg_quote($w, '/'), self::NEGATION_WORDS));

        // Match negation word followed by 0-3 filler words, then the keyword.
        $pattern = '/\b(?:' . $negations . ')\b(?:\s+\S+){0,3}\s+' . preg_quote($keyword, '/') . '\b/iu';

        return (bool) preg_match($pattern, $message);
    }

    /**
     * Return the best keyword match score for a user message.
     * Score = length of longest matched keyword. 0 = no match.
     * Keywords preceded by negation words are skipped.
     */
    public function matchScore(string $message): int
    {
        $keywords = $this->keywords ?? [];
        $best = 0;

        foreach ($keywords as $keyword) {
            $kw = (string) $keyword;
            if ($kw === '') {
                continue;
            }

            // Keyword must appear in message.
            if (!preg_match('/\b' . preg_quote($kw, '/') . '\b/iu', $message)) {
                continue;
            }

            // Skip if the keyword is negated.
            if (self::isNegated($kw, $message)) {
                continue;
            }

            $best = max($best, mb_strlen($kw));
        }

        return $best;
    }

    public function dataModel(): BelongsTo
    {
        return $this->belongsTo(DataModel::class);
    }

    public function chatAgents(): BelongsToMany
    {
        return $this->belongsToMany(ChatAgent::class, 'chat_agent_tool');
    }
}
