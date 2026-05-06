<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
use Illuminate\Support\Facades\Cache;

/**
 * Routes an incoming message to the most relevant specialist ChatAgent.
 *
 * Priority order (highest first): payment > account > bonus > game > triage
 *
 * Session lock: once an agent is selected for a chatId+channel pair, the
 * same agent is reused for CACHE_TTL seconds unless a new message strongly
 * matches a different agent (score difference >= SWITCH_THRESHOLD).
 */
class AgentRouter
{
    private const PRIORITY = ['payment', 'account', 'bonus', 'game', 'triage', 'general'];

    /** Cache TTL in seconds for the per-chat agent lock. */
    private const CACHE_TTL = 300;

    /**
     * Minimum score advantage a new agent must have over the currently locked
     * agent to trigger a topic switch.
     */
    private const SWITCH_THRESHOLD = 2;

    /**
     * Resolve the best ChatAgent for the given message, respecting session lock.
     *
     * @param  string  $message   Raw user message text.
     * @param  mixed   $chatId    Chat/user identifier (stringified).
     * @param  string  $channel   Channel name (telegram, whatsapp, livechat, …).
     */
    public function resolve(string $message, mixed $chatId, string $channel): ChatAgent
    {
        $chatIdStr = (string) $chatId;
        $cacheKey  = "agent_router:{$channel}:{$chatIdStr}";

        // Load all enabled specialist agents (exclude triage — it is the fallback)
        $agents = ChatAgent::where('is_enabled', true)
            ->whereNotNull('agent_type')
            ->where('agent_type', '!=', 'triage')
            ->get();

        // Score each agent against the normalised message
        $scores = [];
        foreach ($agents as $agent) {
            $scores[$agent->id] = $this->scoreMessage($message, $agent->routing_keywords ?? []);
        }

        $bestId = $this->pickBest($scores, $agents);

        if ($bestId === null) {
            // No keyword match — use triage / default fallback
            Cache::forget($cacheKey);

            return $this->getFallback();
        }

        $lockedAgentId = Cache::get($cacheKey);

        if ($lockedAgentId && $lockedAgentId !== $bestId) {
            $lockedScore = $scores[$lockedAgentId] ?? 0;
            $bestScore   = $scores[$bestId] ?? 0;

            if (($bestScore - $lockedScore) >= self::SWITCH_THRESHOLD) {
                // Strong topic switch — reassign
                Cache::put($cacheKey, $bestId, self::CACHE_TTL);
            } else {
                // Stay with locked agent
                $locked = ChatAgent::find($lockedAgentId);
                if ($locked !== null) {
                    return $locked;
                }
                // Locked agent no longer exists — fall through to best
            }
        } else {
            Cache::put($cacheKey, $bestId, self::CACHE_TTL);
        }

        return ChatAgent::find($bestId) ?? $this->getFallback();
    }

    /**
     * Clear the session lock for a specific chat (e.g. after handoff / conversation end).
     */
    public function clearLock(mixed $chatId, string $channel): void
    {
        Cache::forget("agent_router:{$channel}:{$chatId}");
    }

    // -----------------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------------

    private function scoreMessage(string $message, array $keywords): int
    {
        if (empty($keywords)) {
            return 0;
        }

        $normalised = mb_strtolower($message);
        $score      = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($normalised, mb_strtolower((string) $keyword))) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * Pick the highest-scoring agent ID, using PRIORITY as a tiebreaker.
     *
     * @param  array<int, int>                         $scores    agent_id => score
     * @param  \Illuminate\Database\Eloquent\Collection $agents
     * @return int|null
     */
    private function pickBest(array $scores, $agents): ?int
    {
        $positive = array_filter($scores, fn ($s) => $s > 0);

        if (empty($positive)) {
            return null;
        }

        $maxScore   = max($positive);
        $candidates = array_keys(array_filter($positive, fn ($s) => $s === $maxScore));

        $agentsByType = $agents->keyBy('id');

        usort($candidates, function (int $a, int $b) use ($agentsByType): int {
            $typeA = $agentsByType[$a]?->agent_type ?? 'general';
            $typeB = $agentsByType[$b]?->agent_type ?? 'general';
            $prioA = array_search($typeA, self::PRIORITY, true);
            $prioB = array_search($typeB, self::PRIORITY, true);
            $prioA = $prioA === false ? PHP_INT_MAX : $prioA;
            $prioB = $prioB === false ? PHP_INT_MAX : $prioB;

            return $prioA <=> $prioB;
        });

        return $candidates[0] ?? null;
    }

    private function getFallback(): ChatAgent
    {
        return ChatAgent::where('agent_type', 'triage')->where('is_enabled', true)->first()
            ?? ChatAgent::getDefault()
            ?? new ChatAgent(['name' => 'Fallback', 'model' => 'gpt-4.1-mini']);
    }
}
