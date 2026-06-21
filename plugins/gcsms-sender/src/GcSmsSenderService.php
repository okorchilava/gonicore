<?php
declare(strict_types=1);

namespace GcSmsSender;

use GoniCore\Core\Database\QueryBuilder;

/**
 * sender.ge SMS API client.
 *
 * API docs: https://sender.ge/docs/api.php
 *
 * Endpoints:
 *   send.php       – send single SMS
 *   getBalance.php – account balance
 *   callback.php   – delivery status report
 */
final class GcSmsSenderService
{
    private const BASE_URL = 'https://sender.ge/api/';

    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Settings ──────────────────────────────────────────────────────────────

    public function setting(string $key, string $default = ''): string
    {
        $row = $this->qb->table('gcsmssender_settings')->where('key', '=', $key)->first();
        return $row ? (string) $row['value'] : $default;
    }

    public function setSetting(string $key, string $value): void
    {
        $exists = $this->qb->table('gcsmssender_settings')->where('key', '=', $key)->first();
        if ($exists) {
            $this->qb->table('gcsmssender_settings')->where('key', '=', $key)->update(['value' => $value]);
        } else {
            $this->qb->table('gcsmssender_settings')->insert(['key' => $key, 'value' => $value]);
        }
    }

    public function isConfigured(): bool
    {
        return $this->setting('api_key') !== '';
    }

    // ── Send SMS ──────────────────────────────────────────────────────────────

    /**
     * Send a single SMS.
     *
     * @param string $phone    Georgian mobile number (auto-normalised to 9 digits)
     * @param string $text     Message body
     * @param int    $smsNo    1 = with sender title, 2 = without sender title
     * @param int    $priority 0 = default (subscription check), 1 = skip check
     * @return array{result: array, httpCode: int}
     */
    public function send(string $phone, string $text, int $smsNo = 1, int $priority = 0): array
    {
        $phone = $this->normalizePhone($phone);

        [$result, $httpCode] = $this->request('send.php', [
            'smsno'       => $smsNo,
            'destination' => $phone,
            'content'     => $text,
            'priority'    => $priority,
        ]);

        $this->insertLog($phone, $text, $smsNo, $priority, $httpCode, $result);

        return ['result' => $result, 'httpCode' => $httpCode];
    }

    // ── Balance ───────────────────────────────────────────────────────────────

    /**
     * @return array{result: array{balance?: mixed, overdraft?: mixed}, httpCode: int}
     */
    public function balance(): array
    {
        [$result, $httpCode] = $this->request('getBalance.php', []);
        return ['result' => $result, 'httpCode' => $httpCode];
    }

    // ── Delivery status ───────────────────────────────────────────────────────

    /**
     * Check delivery status by messageId.
     * Also updates the matching log row's delivery_status.
     *
     * statusId: 0 = Pending, 1 = Delivered, 2 = Undelivered
     *
     * @return array{result: array, httpCode: int}
     */
    public function checkStatus(string $messageId): array
    {
        [$result, $httpCode] = $this->request('callback.php', [
            'messageId' => $messageId,
        ]);

        // Persist delivery status into the log row
        if ($httpCode === 200 && isset($result['statusId'])) {
            try {
                $this->qb->table('gcsmssender_logs')
                    ->where('message_id', '=', $messageId)
                    ->update(['delivery_status' => (int) $result['statusId']]);
            } catch (\Throwable) {}
        }

        return ['result' => $result, 'httpCode' => $httpCode];
    }

    // ── Logs ──────────────────────────────────────────────────────────────────

    public function logs(int $page = 1, int $perPage = 30): array
    {
        $total = (int) ($this->qb->table('gcsmssender_logs')->count() ?? 0);
        $items = $this->qb->table('gcsmssender_logs')
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
        $this->qb->table('gcsmssender_logs')->where('id', '>', '0')->delete();
    }

    // ── Phone normalisation ───────────────────────────────────────────────────

    /**
     * Strip country code and non-digit chars, return bare 9-digit number.
     *
     * Handles inputs like: 595123456 / +995595123456 / 995595123456 / 0595123456
     */
    public function normalizePhone(string $phone): string
    {
        $d = preg_replace('/\D+/', '', $phone) ?? '';

        // 12 digits with leading 995 → remove country code
        if (strlen($d) === 12 && str_starts_with($d, '995')) {
            return substr($d, 3);
        }
        // 10 digits with leading 0 → remove leading zero
        if (strlen($d) === 10 && str_starts_with($d, '0')) {
            return substr($d, 1);
        }

        return $d;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function insertLog(
        string $phone,
        string $text,
        int    $smsNo,
        int    $priority,
        int    $httpCode,
        array  $result
    ): void {
        try {
            $this->qb->table('gcsmssender_logs')->insert([
                'destination'     => $phone,
                'content'         => mb_substr($text, 0, 500),
                'message_id'      => isset($result['messageId']) ? (string) $result['messageId'] : null,
                'sms_no'          => $smsNo,
                'priority'        => $priority,
                'qnt'             => isset($result['qnt']) ? (int) $result['qnt'] : null,
                'http_code'       => $httpCode,
                'delivery_status' => null,                 // checked later via callback.php
                'response'        => json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable) {}
    }

    /**
     * POST to sender.ge endpoint with form-encoded body.
     *
     * @return array{0: array, 1: int}  [decodedBody, httpCode]
     */
    private function request(string $endpoint, array $params): array
    {
        $apiKey = $this->setting('api_key');
        if ($apiKey === '') {
            return [['error' => 'API key not configured'], 0];
        }

        $params['apikey'] = $apiKey;
        $url = self::BASE_URL . $endpoint;

        $ch = curl_init($url);
        if ($ch === false) {
            return [['error' => 'curl_init failed'], 0];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params, '', '&'),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err !== '') {
            return [['error' => $err], 0];
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            return [['error' => 'Invalid JSON response', 'raw' => (string) $body], $code];
        }

        return [$decoded, $code];
    }
}
