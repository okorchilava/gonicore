<?php

declare(strict_types=1);

namespace GCLiveChat\Ai;

/** OpenAI ChatGPT — Chat Completions API. */
final class OpenAiProvider extends AiProvider
{
    public function complete(string $system, array $messages, string $model, string $apiKey): string
    {
        $msgs = array_merge([['role' => 'system', 'content' => $system]], $messages);

        $data = $this->postJson(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization: Bearer ' . $apiKey,
                'content-type: application/json',
            ],
            [
                'model'       => $model !== '' ? $model : 'gpt-4o-mini',
                'max_tokens'  => 1024,
                'messages'    => $msgs,
                'temperature' => 0.4,
            ],
        );

        return trim((string) ($data['choices'][0]['message']['content'] ?? ''));
    }
}
