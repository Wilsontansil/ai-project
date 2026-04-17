<?php

namespace App\Services\AI\ToolEngines;

use App\Models\Tool;

/**
 * Executes "info" type tools — purely static text responses.
 *
 * When a tool has information_text configured, one entry is picked randomly
 * so repeated queries feel less repetitive.
 */
class InfoToolEngine
{
    /**
     * @return array{mode: string, reply: string}
     */
    public function execute(Tool $tool): array
    {
        if (!empty($tool->information_text)) {
            $texts = (array) $tool->information_text;
            $reply = $texts[array_rand($texts)];

            return [
                'mode' => 'direct',
                'reply' => $reply,
            ];
        }

        return [
            'mode' => 'direct',
            'reply' => "Informasi untuk {$tool->display_name} belum tersedia.",
        ];
    }
}
