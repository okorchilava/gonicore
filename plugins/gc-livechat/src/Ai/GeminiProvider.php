<?php

declare(strict_types=1);

namespace GCLiveChat\Ai;

/** Google Gemini — generateContent API. */
final class GeminiProvider extends AiProvider
{
    public function complete(string $system, array $messages, string $model, string $apiKey): string
    {
        $model = $model !== '' ? $model : 'gemini-2.0-flash';

        $contents = [];
        foreach ($messages as $m) {
            $contents[] = [
                'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content']]],
            ];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey);

        $data = $this->postJson(
            $url,
            ['content-type: application/json'],
            [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents'           => $contents,
            ],
        );

        return trim((string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? ''));
    }
}
