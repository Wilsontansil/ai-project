<?php

namespace App\Support;

class AsyncPipelineException extends \RuntimeException
{
    public static function missingOpenAiKey(): self
    {
        return new self('OpenAI API key is not configured.');
    }

    public static function missingAiAgent(): self
    {
        return new self('AI agent is not configured.');
    }

    public static function missingTelegramToken(): self
    {
        return new self('TELEGRAM_BOT_TOKEN is not configured.');
    }

    public static function unsupportedChannel(string $channel): self
    {
        return new self("ProcessAiReply: unsupported channel '{$channel}'");
    }

    public static function nullTransportResponse(string $provider, string $endpoint): self
    {
        return new self("{$provider} {$endpoint} returned null response");
    }

    public static function transportFailure(string $provider, string $endpoint, int $status): self
    {
        return new self("{$provider} {$endpoint} failed with status {$status}");
    }
}
