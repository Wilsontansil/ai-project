<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
use App\Models\AgentRule;
use App\Models\ProjectSetting;
use App\Models\Tool;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Schema;

/**
 * Builds system prompts and agent context injections for OpenAI requests.
 *
 * Responsibilities:
 *   - Compose the full system prompt from ChatAgent config + agent rules + tool rules.
 *   - Build the per-request customer context block (profile, behaviour, recent history).
 *   - Look up the configured bot name from the _bot_config tool row.
 */
class PromptBuilder
{
    public function buildSystemPrompt(?ChatAgent $chatAgent): string
    {
        $botName = $chatAgent->name ?? $this->getBotName();
        $serverTime = now()->format('Y-m-d H:i:s (l)');
        $serverTimezone = now()->getTimezone()->getName();

        if ($chatAgent && !empty($chatAgent->system_prompt)) {
            $basePrompt = str_replace(
                ['{bot_name}', '{server_time}', '{server_timezone}'],
                [$botName, $serverTime, $serverTimezone],
                $chatAgent->system_prompt
            );
        } else {
            $basePrompt = "You are {$botName}, a friendly customer support assistant for a gaming platform.

        CURRENT SERVER TIME: {$serverTime} ({$serverTimezone})
        Use this as the authoritative current datetime for all time-based calculations (e.g. today, yesterday, last week Monday-Sunday, this month, etc.).
        ";
        }

        $agentRulesPrompt = $this->getAgentRulesPrompt($chatAgent);
        if ($agentRulesPrompt !== '') {
            $basePrompt .= "\n\n" . $agentRulesPrompt;
        }

        $toolRules = $this->getToolRulesPrompt();
        if ($toolRules !== '') {
            $basePrompt .= "\n\n" . $toolRules;
        }

        $websiteKnowledge = $this->getWebsiteKnowledgePrompt();
        if ($websiteKnowledge !== '') {
            $basePrompt .= "\n\n" . $websiteKnowledge;
        }

        return $basePrompt;
    }

    /**
     * Build the optional customer context block injected as a second system message.
     *
     * @param array<string, mixed> $context
     */
    public function buildAgentContextPrompt(array $context, string $channel = 'telegram'): ?string
    {
        if ($context === []) {
            return null;
        }

        $profile = (array) ($context['customer_profile'] ?? []);
        $behavior = (array) ($context['behavior'] ?? []);

        $parts = [
            'Customer context (internal only — do not expose to user):',
            'Current platform: ' . $channel,
        ];

        $supportContact = $this->getSupportContact($channel);
        if ($supportContact !== null) {
            $parts[] = 'Human support contact for this platform: ' . $supportContact;
        }

        if ($profile !== []) {
            $parts[] = 'Profile: ' . json_encode([
                'platform' => $profile['platform'] ?? null,
                'name' => $profile['name'] ?? null,
                'total_messages' => $profile['total_messages'] ?? null,
                'tags' => $profile['tags'] ?? [],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($behavior !== []) {
            $parts[] = 'Behavior: ' . json_encode([
                'intent' => $behavior['intent'] ?? null,
                'sentiment' => $behavior['sentiment'] ?? null,
                'frequency_score' => $behavior['frequency_score'] ?? null,
            ], JSON_UNESCAPED_UNICODE);
        }

        return implode("\n\n", $parts);
    }

    private function getToolRulesPrompt(): string
    {
        if (!Schema::hasTable('tools')) {
            return '';
        }

        $tools = Tool::query()
            ->where('is_enabled', true)
            ->where('tool_name', '!=', '_bot_config')
            ->get();

        $lines = [];

        foreach ($tools as $tool) {
            $rules = trim((string) ($tool->tool_rules ?? ''));
            if ($rules === '') {
                continue;
            }

            $lines[] = "TOOL [{$tool->tool_name}] ({$tool->display_name}) RULES:\n{$rules}";
        }

        if ($lines === []) {
            return '';
        }

        return "PER-TOOL INSTRUCTIONS (follow these strictly when using each tool):\n\n" . implode("\n\n", $lines);
    }

    private function getAgentRulesPrompt(?ChatAgent $chatAgent): string
    {
        if (!Schema::hasTable('agent_rules')) {
            return '';
        }

        $query = AgentRule::query()->where('is_active', true);

        if ($chatAgent) {
            $query->where('chat_agent_id', $chatAgent->id);
        } else {
            $query->whereNull('chat_agent_id');
        }

        $allRules = $query->orderBy('priority')->get();

        if ($allRules->isEmpty()) {
            return '';
        }

        $sections = [];

        // Guidelines
        $guidelines = $allRules->where('type', 'guideline');
        if ($guidelines->isNotEmpty()) {
            $lines = ['RULES (follow these strictly):'];
            foreach ($guidelines as $rule) {
                $tag = strtoupper($rule->category);
                $lines[] = "- [{$tag}] {$rule->instruction}";
            }
            $sections[] = implode("\n", $lines);
        }

        // Forbidden
        $forbidden = $allRules->where('type', 'forbidden');
        if ($forbidden->isNotEmpty()) {
            $lines = ['FORBIDDEN BEHAVIOURS (strictly prohibited — never violate):'];
            foreach ($forbidden as $rule) {
                $levelTag = strtoupper($rule->level);
                $lines[] = "- [{$levelTag}] {$rule->instruction}";
            }
            $sections[] = implode("\n", $lines);
        }

        return implode("\n\n", $sections);
    }

    private function getBotName(): string
    {
        if (!Schema::hasTable('tools')) {
            return 'xoneBot';
        }

        $config = Tool::query()->where('tool_name', '_bot_config')->first();

        return trim((string) ($config?->meta['bot_name'] ?? 'xoneBot')) ?: 'xoneBot';
    }

    private function getWebsiteKnowledgePrompt(): string
    {
        if (!Schema::hasTable('website_pages')) {
            return '';
        }

        $pages = WebsitePage::query()
            ->where('status', 'scraped')
            ->whereNotNull('content')
            ->get();

        if ($pages->isEmpty()) {
            return '';
        }

        $maxPerPage = 3000;
        $blocks = [];

        foreach ($pages as $page) {
            $content = mb_substr(trim($page->content), 0, $maxPerPage);
            $title = $page->title ?: 'Untitled';
            $blocks[] = "=== {$title} ({$page->url}) ===\n{$content}";
        }

        $combined = implode("\n\n", $blocks);

        return "WEBSITE KNOWLEDGE (use this information to answer questions about our website, products, and services):\n\n" . $combined;
    }

    private function getSupportContact(string $channel): ?string
    {
        $map = [
            'telegram' => ProjectSetting::getValue('support_telegram_tag'),
            'whatsapp' => ProjectSetting::getValue('support_whatsapp_phone'),
            'livechat' => ProjectSetting::getValue('support_livechat_url'),
        ];

        $contact = $map[$channel] ?? null;

        return $contact !== null && $contact !== '' ? $contact : null;
    }
}
