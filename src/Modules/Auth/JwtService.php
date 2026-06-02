<?php

declare(strict_types=1);

namespace GoniCore\Modules\Auth;

use JsonException;
use RuntimeException;

/**
 * Minimal native JWT service — HMAC-SHA256, no external dependencies.
 *
 * Standard claims injected automatically on encode():
 *   iat — issued-at  (current Unix timestamp)
 *   exp — expiration (iat + $ttl seconds)
 *
 * Caller-supplied claims (pass via $claims array):
 *   sub  — subject / user ID
 *   role — user role string
 *   (any other custom claims are preserved)
 */
final class JwtService
{
    public function __construct(
        private readonly string $secret,
        private readonly int    $ttl = 3600,
    ) {
        if (strlen($this->secret) < 32) {
            throw new RuntimeException(
                'JWT_SECRET must be at least 32 characters long.'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Create a signed JWT from arbitrary claims.
     *
     * @param array<string, mixed> $claims
     * @throws RuntimeException on JSON encoding failure.
     */
    public function encode(array $claims): string
    {
        $now = time();

        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ]);

        try {
            $header    = $this->b64u(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $payload   = $this->b64u(json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to encode JWT payload: ' . $e->getMessage(), 0, $e);
        }

        $signature = $this->sign("{$header}.{$payload}");

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Verify and decode a JWT. Returns the payload claims array.
     *
     * @return array<string, mixed>
     * @throws RuntimeException  on malformed token, bad signature, or expiration.
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed token: expected 3 segments.');
        }

        [$header, $payload, $signature] = $parts;

        // Constant-time signature verification.
        if (!hash_equals($this->sign("{$header}.{$payload}"), $signature)) {
            throw new RuntimeException('Token signature is invalid.');
        }

        try {
            $claims = json_decode(
                $this->b64uDecode($payload),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to decode token payload.', 0, $e);
        }

        if (!is_array($claims)) {
            throw new RuntimeException('Token payload is not a valid JSON object.');
        }

        if (isset($claims['exp']) && $claims['exp'] < time()) {
            throw new RuntimeException('Token has expired.');
        }

        return $claims;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    private function sign(string $data): string
    {
        return rtrim(
            strtr(
                base64_encode(hash_hmac('sha256', $data, $this->secret, true)),
                '+/',
                '-_',
            ),
            '=',
        );
    }

    private function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64uDecode(string $data): string
    {
        $pad  = (4 - strlen($data) % 4) % 4;
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', $pad));
    }
}
