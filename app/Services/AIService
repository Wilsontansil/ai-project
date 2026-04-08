<?php

namespace App\Services;

use OpenAI;

class AIService
{
    public function reply($message)
    {
        $apikey = env('OPENAI_API_KEY');
        $client = OpenAI::client($apikey);

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful customer service assistant'
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ]);

        return $response->choices[0]->message->content;
    }
}