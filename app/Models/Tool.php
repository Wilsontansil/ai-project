<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_name',
        'display_name',
        'description',
        'class_name',
        'slug',
        'is_enabled',
        'parameters',
        'keywords',
        'missing_message',
        'information_text',
        'meta',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'parameters' => 'array',
        'keywords' => 'array',
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

    /**
     * Create a new instance of the tool's service class (for execution only).
     * Returns null if no class_name is set or class does not exist.
     */
    public function newServiceInstance(): ?object
    {
        if (empty($this->class_name) || !class_exists($this->class_name)) {
            return null;
        }

        return new ($this->class_name)();
    }
}
