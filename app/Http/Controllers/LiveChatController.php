<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveChatController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Received LiveChat webhook', ['payload' => $request->all()]);
        $challenge = $request->input('challenge');

        if(!$challenge) {
    //             {
    //     "responses": [
    //         {
    //             "type": "text",
    //             "delay": 1000,
    //             "message": "Have a great day!"
    //         }
    //     ],
    //     "attributes": {
    //         "foo": "bar",
    //         "baz": ""
    //     }
    // }

            return response()->json([
                'responses' => [
                    [
                        'type' => 'text',
                        'delay' => 1000,
                        'message' => 'Have a great day!',
                    ],
                ],
                'attributes' => [
                    'foo' => 'bar',
                    'baz' => '',
                ],
            ]);
        }

        return $challenge ? response($challenge, 200) : response()->json(['status' => 'ok']);
    }
}
