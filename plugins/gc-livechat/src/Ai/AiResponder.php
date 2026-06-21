<?php

declare(strict_types=1);

namespace GCLiveChat\Ai;

use GoniCore\Core\Database\QueryBuilder;

/**
 * Orchestrates an AI reply: picks the admin-selected provider, builds a system
 * prompt from the site's own content + custom instructions + FAQ, normalises the
 * conversation history, and returns the assistant's answer.
 *
 * Provider + key + model + prompts are read from core settings (gc_setting):
 *   livechat_provider (claude|openai|gemini), livechat_api_key, livechat_model,
 *   livechat_system_prompt, livechat_faq, livechat_use_site_content (1|0).
 */
final class AiResponder
{
    public const PROVIDERS = [
        'claude' => 'Claude (Anthropic)',
        'openai' => 'ChatGPT (OpenAI)',
        'gemini' => 'Gemini (Google)',
    ];

    public const DEFAULT_MODELS = [
        'claude' => 'claude-sonnet-4-6',
        'openai' => 'gpt-4o-mini',
        'gemini' => 'gemini-2.0-flash',
    ];

    public function __construct(private readonly QueryBuilder $qb) {}

    public function isConfigured(): bool
    {
        return trim((string) gc_setting('livechat_api_key', '')) !== '';
    }

    public function providerKey(): string
    {
        $p = (string) gc_setting('livechat_provider', 'claude');
        return isset(self::PROVIDERS[$p]) ? $p : 'claude';
    }

    /**
     * Generate a reply for the given conversation history.
     * @param list<array<string,mixed>> $history  rows of {sender, body}, oldest first
     */
    public function reply(array $history): string
    {
        $provider = $this->makeProvider($this->providerKey());
        $model    = trim((string) gc_setting('livechat_model', ''));
        $key      = trim((string) gc_setting('livechat_api_key', ''));

        $messages = $this->normalize($history);
        if ($messages === []) {
            return '';
        }

        return $provider->complete($this->systemPrompt(), $messages, $model, $key);
    }

    private function makeProvider(string $key): AiProvider
    {
        return match ($key) {
            'openai' => new OpenAiProvider(),
            'gemini' => new GeminiProvider(),
            default  => new ClaudeProvider(),
        };
    }

    /** Assemble the system prompt from settings + (optionally) site content. */
    public function systemPrompt(): string
    {
        $site = (string) gc_setting('site_name', 'this website');

        $parts = [];
        $parts[] = "You are a friendly, concise customer-support assistant for \"{$site}\". "
            . "Reply in the visitor's own language. Keep answers short (2–4 sentences). "
            . "Only use the information provided below or general knowledge; never invent specific facts, prices, or policies. "
            . "If you cannot help, or the visitor explicitly asks for a person, tell them to tap the \"Talk to a human\" button to reach an operator.";

        $custom = trim((string) gc_setting('livechat_system_prompt', ''));
        if ($custom !== '') {
            $parts[] = "Additional instructions from the site owner:\n" . $custom;
        }

        $faq = trim((string) gc_setting('livechat_faq', ''));
        if ($faq !== '') {
            $parts[] = "Frequently asked questions:\n" . $faq;
        }

        if ((string) gc_setting('livechat_use_site_content', '1') === '1') {
            $content = $this->siteContent();
            if ($content !== '') {
                $parts[] = "Reference content from the site (titles and summaries):\n" . $content;
            }
        }

        return implode("\n\n", $parts);
    }

    /** Compact digest of recent published posts/pages for grounding. */
    private function siteContent(): string
    {
        try {
            $rows = $this->qb->table('posts')
                ->where('status', '=', 'published')
                ->orderBy('created_at', 'DESC')
                ->limit(20)
                ->get();
        } catch (\Throwable) {
            return '';
        }

        $lines = [];
        foreach ($rows as $r) {
            $title = trim((string) ($r['title'] ?? ''));
            if ($title === '') continue;
            $raw = (string) ($r['excerpt'] ?? '');
            if ($raw === '') {
                $raw = (string) ($r['content'] ?? '');
            }
            $snippet = trim(preg_replace('/\s+/', ' ', strip_tags($raw)) ?? '');
            $snippet = mb_substr($snippet, 0, 200);
            $lines[] = '- ' . $title . ($snippet !== '' ? ': ' . $snippet : '');
        }

        return mb_substr(implode("\n", $lines), 0, 4000);
    }

    /**
     * Map stored history → alternating user/assistant turns starting with user.
     * @param list<array<string,mixed>> $history
     * @return list<array{role:string,content:string}>
     */
    private function normalize(array $history): array
    {
        $out = [];
        foreach ($history as $h) {
            $role    = ((string) ($h['sender'] ?? 'visitor')) === 'visitor' ? 'user' : 'assistant';
            $content = trim((string) ($h['body'] ?? ''));
            if ($content === '') continue;

            if ($out !== [] && $out[count($out) - 1]['role'] === $role) {
                $out[count($out) - 1]['content'] .= "\n" . $content;
            } else {
                $out[] = ['role' => $role, 'content' => $content];
            }
        }
        // Providers (Claude) require the first turn to be the user.
        while ($out !== [] && $out[0]['role'] !== 'user') {
            array_shift($out);
        }
        return $out;
    }
}
