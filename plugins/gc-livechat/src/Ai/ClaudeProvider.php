<?php

declare(strict_types=1);

namespace GCLiveChat\Ai;

/** Anthropic Claude — Messages API. */
final class ClaudeProvider extends AiProvider
{
    public function complete(string $system, array $messages, string $model, string $apiKey): string
    {
        $data = $this->postJson(
            'https://api.anthropic.com/v1/messages',
            [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            [
                'model'      => $model !== '' ? $model : 'claude-sonnet-4-6',
                'max_tokens' => 1024,
                'system'     => $system,
                'messages'   => $messages,
            ],
        );

        return trim((string) ($data['content'][0]['text'] ?? ''));
    }
}
