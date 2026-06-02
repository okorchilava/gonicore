<?php

declare(strict_types=1);

namespace GoniCore\Core\Http\Middleware;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Auth\JwtService;
use RuntimeException;

/**
 * JWT Bearer token authentication middleware.
 *
 * Expects an `Authorization: Bearer <token>` header.
 * On success, injects the following attributes into the Request:
 *   - auth    (full decoded payload)
 *   - userId  (int — the `sub` claim)
 *   - userRole (string — the `role` claim, default 'viewer')
 *
 * Returns HTTP 401 if the header is missing, malformed, or the token is invalid.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JwtService $jwt) {}

    public function process(Request $request, callable $next): Response
    {
        $header = $request->header('Authorization');

        if ($header === null || !str_starts_with($header, 'Bearer ')) {
            return Response::unauthorized('Authentication required. Provide a Bearer token.');
        }

        $token = substr($header, 7);

        try {
            $payload = $this->jwt->decode($token);
        } catch (RuntimeException $e) {
            return Response::unauthorized($e->getMessage());
        }

        $request = $request
            ->withAttribute('auth',     $payload)
            ->withAttribute('userId',   (int) ($payload['sub'] ?? 0))
            ->withAttribute('userRole', (string) ($payload['role'] ?? 'viewer'));

        return $next($request);
    }
}
