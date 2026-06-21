<?php
declare(strict_types=1);

namespace GcSms;

use GoniCore\Core\Database\QueryBuilder;

/**
 * GoSMS.ge API client + log helper.
 *
 * All public send-methods return the raw decoded JSON array from the API.
 * On network/cURL errors the array contains ['success'=>false, 'errorCode'=>-1, 'error'=>'...'].
 */
final class GcSmsService
{
    private const BASE_URL = 'https://api.gosms.ge/api/';

    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Settings ──────────────────────────────────────────────────────────────

    public function setting(string $key, string $default = ''): string
    {
        $row = $this->qb->table('gcsms_settings')->where('key', '=', $key)->first();
        return $row ? (string) $row['value'] : $default;
    }

    public function setSetting(string $key, string $value): void
    {
        $exists = $this->qb->table('gcsms_settings')->where('key', '=', $key)->first();
        if ($exists) {
            $this->qb->table('gcsms_settings')->where('key', '=', $key)->update(['value' => $value]);
        } else {
            $this->qb->table('gcsms_settings')->insert(['key' => $key, 'value' => $value]);
        }
    }

    public function isConfigured(): bool
    {
        return $this->setting('api_key') !== '' && $this->setting('sender_name') !== '';
    }

    // ── Webhooks ────────────────────────────────────────────────────────────────

    /** Get the webhook auth token, generating + persisting one on first use. */
    public function webhookToken(): string
    {
        $t = $this->setting('webhook_token');
        if ($t === '') {
            $t = bin2hex(random_bytes(24));
            $this->setSetting('webhook_token', $t);
        }
        return $t;
    }

    public function regenerateWebhookToken(): string
    {
        $t = bin2hex(random_bytes(24));
        $this->setSetting('webhook_token', $t);
        return $t;
    }

    /** Constant-time check of an incoming X-Webhook-Token header. */
    public function verifyWebhookToken(?string $token): bool
    {
        $expected = $this->setting('webhook_token');
        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    /**
     * Store an inbound short-number reply (from the inbound webhook).
     *
     * @param array<string,mixed> $d  Decoded request body (from/to/text/sendAt/noSms).
     */
    public function recordInbound(array $d): void
    {
        $this->qb->table('gcsms_inbound')->insert([
            'from_number' => mb_substr((string) ($d['from'] ?? ''), 0, 100),
            'to_number'   => mb_substr((string) ($d['to'] ?? ''), 0, 100),
            'message'     => (string) ($d['text'] ?? ''),
            'no_sms'      => filter_var($d['noSms'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'sent_at'     => $this->normDate($d['sendAt'] ?? null),
        ]);
    }

    /**
     * Update a sent message's delivery status, matched by the provider messageId.
     *
     * @return int  Number of log rows updated (0 = messageId not found locally).
     */
    public function updateDeliveryStatus(string $messageId, string $status): int
    {
        if ($messageId === '' || $status === '') return 0;
        return $this->qb->table('gcsms_logs')
            ->where('message_id', '=', $messageId)
            ->update(['status' => strtolower($status)]);
    }

    /** @return array{items:list<array<string,mixed>>,total:int,pages:int} */
    public function inbound(int $page = 1, int $perPage = 30): array
    {
        $total = (int) ($this->qb->table('gcsms_inbound')->count() ?? 0);
        $items = $this->qb->table('gcsms_inbound')
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get() ?: [];
        return ['items' => $items, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function unreadInboundCount(): int
    {
        try { return (int) $this->qb->table('gcsms_inbound')->where('is_read', '=', '0')->count(); }
        catch (\Throwable) { return 0; }
    }

    public function markInboundRead(): void
    {
        try { $this->qb->table('gcsms_inbound')->where('is_read', '=', '0')->update(['is_read' => 1]); }
        catch (\Throwable) {}
    }

    public function clearInbound(): void
    {
        $this->qb->table('gcsms_inbound')->where('id', '>', '0')->delete();
    }

    /** Normalise an ISO/any date string to MySQL DATETIME, or null. */
    private function normDate(mixed $v): ?string
    {
        if (!is_string($v) || trim($v) === '') return null;
        $ts = strtotime($v);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }

    // ── Send single SMS ───────────────────────────────────────────────────────

    /**
     * Send a single SMS.
     *
     * @param string $to     Phone number (e.g. 995555123456)
     * @param string $text   Message body
     * @param bool   $urgent Bypass message blocks
     */
    public function send(string $to, string $text, bool $urgent = false): array
    {
        $result = $this->request('sendsms', [
            'api_key' => $this->setting('api_key'),
            'from'    => $this->setting('sender_name'),
            'to'      => $to,
            'text'    => $text,
            'urgent'  => $urgent,
        ]);
        $this->log('single', $to, $text, $result);
        return $result;
    }

    // ── Send bulk SMS ─────────────────────────────────────────────────────────

    /**
     * Send the same message to multiple recipients (max 1000).
     *
     * @param string[] $to          List of phone numbers
     * @param string   $text        Message body
     * @param bool     $urgent      Bypass message blocks
     * @param string   $noSmsNumber Opt-out number shown to recipients
     */
    public function sendBulk(array $to, string $text, bool $urgent = false, string $noSmsNumber = ''): array
    {
        $payload = [
            'api_key' => $this->setting('api_key'),
            'from'    => $this->setting('sender_name'),
            'to'      => array_values($to),
            'text'    => $text,
            'urgent'  => $urgent,
        ];
        if ($noSmsNumber !== '') {
            $payload['noSmsNumber'] = $noSmsNumber;
        }
        $result = $this->request('sendbulk', $payload);
        $this->log('bulk', implode(',', $to), $text, $result);
        return $result;
    }

    // ── Check SMS status ──────────────────────────────────────────────────────

    public function checkStatus(int|string $messageId): array
    {
        return $this->request('checksms', [
            'api_key'   => $this->setting('api_key'),
            'messageId' => (string) $messageId,
        ]);
    }

    // ── OTP ───────────────────────────────────────────────────────────────────

    /**
     * Send a one-time password to the given phone.
     * Returns hash that must be passed to verifyOtp().
     */
    public function sendOtp(string $phone): array
    {
        $result = $this->request('otp/send', [
            'api_key' => $this->setting('api_key'),
            'phone'   => $phone,
        ]);
        $this->log('otp', $phone, '[OTP]', $result);
        return $result;
    }

    /**
     * Verify the OTP code entered by the user.
     */
    public function verifyOtp(string $phone, string $hash, string $code): array
    {
        return $this->request('otp/verify', [
            'api_key' => $this->setting('api_key'),
            'phone'   => $phone,
            'hash'    => $hash,
            'code'    => $code,
        ]);
    }

    // ── Balance ───────────────────────────────────────────────────────────────

    public function balance(): array
    {
        return $this->request('sms-balance', [
            'api_key' => $this->setting('api_key'),
        ]);
    }

    // ── Create sender name ────────────────────────────────────────────────────

    public function createSender(string $name): array
    {
        return $this->request('sender', [
            'api_key' => $this->setting('api_key'),
            'name'    => $name,
        ]);
    }

    // ── Logs ──────────────────────────────────────────────────────────────────

    public function logs(int $page = 1, int $perPage = 30): array
    {
        $total = (int) ($this->qb->table('gcsms_logs')->count() ?? 0);
        $items = $this->qb->table('gcsms_logs')
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get() ?: [];
        return [
            'items' => $items,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function clearLogs(): void
    {
        // Delete all rows; WHERE id > 0 is used for QueryBuilder compatibility
        $this->qb->table('gcsms_logs')->where('id', '>', '0')->delete();
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function log(string $type, string $phone, string $message, array $response): void
    {
        try {
            $this->qb->table('gcsms_logs')->insert([
                'type'       => $type,
                'phone'      => mb_substr($phone, 0, 100),
                'message'    => $message,
                'message_id' => isset($response['messageId']) ? (string) $response['messageId'] : null,
                'status'     => ($response['success'] ?? false) ? 'sent' : 'failed',
                'response'   => json_encode($response, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable) {}
    }

    private function request(string $endpoint, array $data): array
    {
        $url = self::BASE_URL . $endpoint;
        $ch  = curl_init($url);
        if ($ch === false) {
            return ['success' => false, 'errorCode' => -1, 'error' => 'curl_init failed'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            return ['success' => false, 'errorCode' => -1, 'error' => $err];
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'errorCode' => -1, 'error' => 'Invalid JSON response', 'raw' => (string) $body];
        }
        return $decoded;
    }
}
