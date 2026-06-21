<?php

declare(strict_types=1);

namespace GCLiveChat\Ai;

use RuntimeException;

/**
 * Base class for chat AI providers. Subclasses implement complete() for their
 * specific HTTP API; this base provides a safe JSON POST helper.
 */
abstract class AiProvider
{
    /**
     * @param list<array{role:string,content:string}> $messages  Alternating user/assistant turns.
     * @return string  The assistant's reply text.
     */
    abstract public function complete(string $system, array $messages, string $model, string $apiKey): string;

    /**
     * POST a JSON body and return the decoded response.
     *
     * @param list<string>         $headers
     * @param array<string,mixed>  $body
     * @return array<string,mixed>
     * @throws RuntimeException on transport/HTTP/JSON failure.
     */
    protected function postJson(string $url, array $headers, array $body): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for the AI provider.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('AI request failed: ' . $cerr);
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('AI returned a non-JSON response.');
        }

        if ($code < 200 || $code >= 300) {
            $msg = $data['error']['message'] ?? ($data['error']['status'] ?? ('HTTP ' . $code));
            throw new RuntimeException('AI error: ' . (is_string($msg) ? $msg : 'HTTP ' . $code));
        }

        return $data;
    }
}
