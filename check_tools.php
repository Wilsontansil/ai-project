<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tools = \App\Models\Tool::where('is_enabled', true)->get();

foreach ($tools as $tool) {
    echo "Tool: " . $tool->tool_name . "\n";
    echo "  - endpoints: " . json_encode($tool->endpoints, JSON_PRETTY_PRINT) . "\n";
    echo "  - data_model_id: " . $tool->data_model_id . "\n";
    echo "  - parameters: " . json_encode($tool->parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    echo "\n";
}
