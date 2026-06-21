<?php
declare(strict_types=1);

namespace GcSms;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

/**
 * Public webhook endpoints for the SMS provider (gosms.ge).
 *
 * Both are authenticated by the `X-Webhook-Token` header (no login / no CSRF)
 * and return HTTP 200 on success, per the provider's spec:
 *
 *   POST /gcsms/webhook/inbound  — incoming short-number replies
 *   POST /gcsms/webhook/status   — delivery-status callbacks
 */
final class GcSmsWebhookController
{
    public function __construct(private readonly GcSmsService $sms) {}

    /**
     * Incoming short-number reply.
     * Body: { from, to, text, sendAt, noSms }
     */
    public function inbound(Request $r): Response
    {
        if (!$this->authorized($r)) {
            return Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        $body = $this->body($r);
        if (($body['from'] ?? '') === '' && ($body['text'] ?? '') === '') {
            return Response::json(['ok' => false, 'error' => 'Invalid payload'], 400);
        }
        try {
            $this->sms->recordInbound($body);
        } catch (\Throwable $e) {
            error_log('[gcsms] inbound webhook store failed: ' . $e->getMessage());
            return Response::json(['ok' => false, 'error' => 'Server error'], 500);
        }
        return Response::json(['ok' => true]);
    }

    /**
     * Delivery-status callback.
     * Body: { messageId, from, to, status, text, sendAt }
     * status ∈ DELIVERED | REJECTED | EXPIRED | DELETED | QUEUE
     */
    public function status(Request $r): Response
    {
        if (!$this->authorized($r)) {
            return Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        $body      = $this->body($r);
        $messageId = (string) ($body['messageId'] ?? '');
        $status    = (string) ($body['status'] ?? '');
        if ($messageId === '' || $status === '') {
            return Response::json(['ok' => false, 'error' => 'messageId and status are required'], 400);
        }
        try {
            $updated = $this->sms->updateDeliveryStatus($messageId, $status);
        } catch (\Throwable $e) {
            error_log('[gcsms] status webhook failed: ' . $e->getMessage());
            return Response::json(['ok' => false, 'error' => 'Server error'], 500);
        }
        // Always 200 so the provider doesn't retry; `matched` reports whether a
        // local sent-log row was found for this messageId.
        return Response::json(['ok' => true, 'matched' => $updated > 0]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function authorized(Request $r): bool
    {
        return $this->sms->verifyWebhookToken($r->header('X-Webhook-Token'));
    }

    /** @return array<string,mixed> */
    private function body(Request $r): array
    {
        $j = $r->json();
        if (!empty($j)) return $j;
        // Fallback: decode the raw body even if the Content-Type header is off.
        $raw = $r->body();
        if ($raw !== '') {
            $d = json_decode($raw, true);
            if (is_array($d)) return $d;
        }
        return [];
    }
}
