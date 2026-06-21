<?php

declare(strict_types=1);

namespace BogPayment;

use GoniCore\Core\Database\QueryBuilder;

/**
 * Bank of Georgia Payment Gateway — full API client.
 *
 * @see https://api.bog.ge/docs/payments/introduction
 *
 * Covers:
 *  - OAuth2 token (with local cache)
 *  - Create order  (standard + preauthorization)
 *  - Get receipt / payment details
 *  - Refund (full or partial)
 *  - Preauthorization approve / cancel
 *  - Callback signature verification (SHA256withRSA)
 */
final class BogService
{
    // ── Production API URLs ───────────────────────────────────────────────────

    private const PROD_TOKEN_URL       = 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token';
    private const PROD_ORDERS_URL      = 'https://api.bog.ge/payments/v1/ecommerce/orders';
    private const PROD_RECEIPT_URL     = 'https://api.bog.ge/payments/v1/receipt/';
    private const PROD_REFUND_URL      = 'https://api.bog.ge/payments/v1/payment/refund/';
    private const PROD_PREAUTH_APPROVE = 'https://api.bog.ge/payments/v1/payment/authorization/approve/';
    private const PROD_PREAUTH_CANCEL  = 'https://api.bog.ge/payments/v1/payment/authorization/cancel/';

    // ── Sandbox API URLs ──────────────────────────────────────────────────────

    private const SANDBOX_TOKEN_URL       = 'https://oauth2-sandbox.bog.ge/auth/realms/bog/protocol/openid-connect/token';
    private const SANDBOX_ORDERS_URL      = 'https://api-sandbox.bog.ge/payments/v1/ecommerce/orders';
    private const SANDBOX_RECEIPT_URL     = 'https://api-sandbox.bog.ge/payments/v1/receipt/';
    private const SANDBOX_REFUND_URL      = 'https://api-sandbox.bog.ge/payments/v1/payment/refund/';
    private const SANDBOX_PREAUTH_APPROVE = 'https://api-sandbox.bog.ge/payments/v1/payment/authorization/approve/';
    private const SANDBOX_PREAUTH_CANCEL  = 'https://api-sandbox.bog.ge/payments/v1/payment/authorization/cancel/';

    /** RSA public key — production callback signature verification. */
    private const PROD_PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----\n"
        . "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu4RUyAw3+CdkS3ZNILQh\n"
        . "zHI9Hemo+vKB9U2BSabppkKjzjjkf+0Sm76hSMiu/HFtYhqWOESryoCDJoqffY0Q\n"
        . "1VNt25aTxbj068QNUtnxQ7KQVLA+pG0smf+EBWlS1vBEAFbIas9d8c9b9sSEkTrr\n"
        . "TYQ90WIM8bGB6S/KLVoT1a7SnzabjoLc5Qf/SLDG5fu8dH8zckyeYKdRKSBJKvhx\n"
        . "tcBuHV4f7qsynQT+f2UYbESX/TLHwT5qFWZDHZ0YUOUIvb8n7JujVSGZO9/+ll/g\n"
        . "4ZIWhC1MlJgPObDwRkRd8NFOopgxMcMsDIZIoLbWKhHVq67hdbwpAq9K9WMmEhPn\n"
        . "PwIDAQAB\n"
        . "-----END PUBLIC KEY-----";

    /** RSA public key — sandbox callback signature verification. */
    private const SANDBOX_PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----\n"
        . "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqczfAuhtxw2iF68kS0Hy\n"
        . "bGSv0ZlDAjsXh6VC8avDl3Vxa9qCn6Pzl37Tl2Z21WodiISLeXdhCtOMTeLNUBeb\n"
        . "CYD31y2/MwnhLYqlCk2bOh29fyPc1iT5Eu/k/1IaNRrK9/UVZaTkhOMeEm+aL4y8\n"
        . "5XsE4UjqftEmwrAdbO2G4cCpuoMC9ZXG9gAdr2BFN6i2Vt9eCen5Poj7E1ik7s8T\n"
        . "GyzploVV0NflhwBGeWnvQANUQGr87gsP5k2JG1z5EwnMybJQ7i3XT726rJMaV6QW\n"
        . "sY5hP72Mtv1I1zL2d9FXm9FWOzbpcXCyxuEBXvqqOHzogri8C7KRRYKyk97Ri7D6\n"
        . "8wIDAQAB\n"
        . "-----END PUBLIC KEY-----";

    /** Human-readable payment response codes from BOG docs. */
    public const RESPONSE_CODES = [
        '100' => 'Successful payment',
        '101' => 'Card usage restricted – contact issuing bank',
        '102' => 'Saved card not found',
        '103' => 'Card declined',
        '104' => 'Transaction limit exceeded',
        '105' => 'Card has expired',
        '106' => 'Amount limit exceeded',
        '107' => 'Insufficient funds',
        '108' => 'Authentication rejected',
        '109' => 'Technical malfunction',
        '110' => 'Transaction expired',
        '111' => 'Authentication timeout',
        '112' => 'General system error',
        '122' => 'Acquirer bank declined payment',
        '199' => 'Unknown response',
        '200' => 'Successful preauthorization',
        '161' => 'Refund failed – contact issuer',
        '162' => 'Refund declined by issuing bank',
        '163' => 'Insufficient funds for refund',
        '167' => 'Card expired – refund to active card',
        '179' => 'Unknown refund response',
    ];

    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Settings ──────────────────────────────────────────────────────────────

    public function setting(string $key, string $default = ''): string
    {
        try {
            $this->ensureSettingsTable();
            $row = $this->qb->table('gs_settings')->where('key', '=', 'bog_' . $key)->first();
            return $row ? (string) $row['value'] : $default;
        } catch (\Throwable) {
            // Settings store unavailable (e.g. gs_settings missing) — never 500.
            return $default;
        }
    }

    public function setSetting(string $key, string $value): void
    {
        try {
            $this->ensureSettingsTable();
            $k      = 'bog_' . $key;
            $exists = $this->qb->table('gs_settings')->where('key', '=', $k)->first();
            if ($exists) {
                $this->qb->table('gs_settings')->where('key', '=', $k)->update(['value' => $value]);
            } else {
                $this->qb->table('gs_settings')->insert(['key' => $k, 'value' => $value]);
            }
        } catch (\Throwable) {
            // Storage unavailable — settings just won't persist; never fatal.
        }
    }

    /**
     * BOG stores its settings in goni-store's shared `gs_settings` key/value
     * table. If the store plugin hasn't created it yet (e.g. its migration was
     * interrupted), create it here so the settings page never 500s.
     * CREATE TABLE IF NOT EXISTS is idempotent and won't clash with the store.
     */
    private function ensureSettingsTable(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        \GoniCore\Core\Container\Container::global()
            ->get(\GoniCore\Core\Database\Connection::class)
            ->execute(
                "CREATE TABLE IF NOT EXISTS `gs_settings` ("
                . "`key` VARCHAR(100) NOT NULL PRIMARY KEY, "
                . "`value` LONGTEXT NOT NULL DEFAULT ''"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
    }

    public function isEnabled(): bool
    {
        return $this->setting('enabled', '0') === '1'
            && $this->setting('client_id')     !== ''
            && $this->setting('client_secret') !== '';
    }

    public function isSandbox(): bool
    {
        return $this->setting('sandbox', '0') === '1';
    }

    // ── URL helpers (prod vs sandbox) ────────────────────────────────────────

    private function tokenUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_TOKEN_URL : self::PROD_TOKEN_URL;
    }

    private function ordersUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_ORDERS_URL : self::PROD_ORDERS_URL;
    }

    private function receiptUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_RECEIPT_URL : self::PROD_RECEIPT_URL;
    }

    private function refundUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_REFUND_URL : self::PROD_REFUND_URL;
    }

    private function preAuthApproveUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_PREAUTH_APPROVE : self::PROD_PREAUTH_APPROVE;
    }

    private function preAuthCancelUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_PREAUTH_CANCEL : self::PROD_PREAUTH_CANCEL;
    }

    private function activePublicKey(): string
    {
        return $this->isSandbox() ? self::SANDBOX_PUBLIC_KEY : self::PROD_PUBLIC_KEY;
    }

    // ── OAuth 2.0 token (with 30-second early expiry buffer) ─────────────────

    public function getToken(): ?string
    {
        // Use separate cache keys per environment to avoid cross-env token pollution
        $suffix    = $this->isSandbox() ? '_sandbox' : '';
        $cached    = $this->setting('token' . $suffix);
        $expiresAt = (int) $this->setting('token_expires_at' . $suffix, '0');

        if ($cached && time() < $expiresAt) {
            return $cached;
        }

        $clientId     = $this->setting('client_id');
        $clientSecret = $this->setting('client_secret');
        if (!$clientId || !$clientSecret) return null;

        $res = $this->http('POST', $this->tokenUrl(), [
            'headers'     => ['Content-Type: application/x-www-form-urlencoded'],
            'body'        => 'grant_type=client_credentials',
            'basic_auth'  => $clientId . ':' . $clientSecret,
        ]);

        if (!$res || empty($res['data']['access_token'])) {
            error_log('[BOG] Token request failed.'
                . ' env=' . ($this->isSandbox() ? 'sandbox' : 'production')
                . ' code=' . ($res['code'] ?? 'no-response')
                . ' body=' . json_encode($res['data'] ?? null));
            return null;
        }

        $ttl = max(60, (int) ($res['data']['expires_in'] ?? 300)) - 30;
        $this->setSetting('token' . $suffix, $res['data']['access_token']);
        $this->setSetting('token_expires_at' . $suffix, (string) (time() + $ttl));

        return $res['data']['access_token'];
    }

    // ── Create payment order ──────────────────────────────────────────────────

    /**
     * Create a BOG payment order.
     *
     * @param  string $capture   'automatic' (default) or 'manual' (preauthorization)
     * @param  array  $methods   Allowed: card, google_pay, apple_pay, bog_p2p, bog_loyalty, bnpl
     * @return array{bog_order_id:string, redirect_url:string}|null
     */
    public function createOrder(
        string $externalOrderId,
        float  $total,
        string $currency,
        array  $basket,
        string $callbackUrl,
        string $successUrl,
        string $failUrl,
        array  $buyer       = [],
        string $capture     = 'automatic',
        array  $methods     = [],
        string $description = '',
        int    $ttlMinutes  = 15,
    ): ?array {
        $token = $this->getToken();
        if (!$token) return null;

        $payload = [
            'callback_url'      => $callbackUrl,
            'external_order_id' => $externalOrderId,
            'capture'           => $capture,
            'ttl'               => $ttlMinutes,
            'purchase_units'    => [
                'currency'     => strtoupper($currency),
                'total_amount' => round($total, 2),
                'basket'       => $basket,
            ],
            'redirect_urls' => [
                'success' => $successUrl,
                'fail'    => $failUrl,
            ],
        ];

        if (!empty($methods)) {
            $payload['payment_method'] = $methods;
        }
        if (!empty($buyer)) {
            $payload['buyer'] = $buyer;
        }

        $res = $this->http('POST', $this->ordersUrl(), [
            'token'   => $token,
            'json'    => $payload,
            'lang'    => 'ka',
        ]);

        if (!$res || $res['code'] !== 200 || empty($res['data']['id'])) {
            error_log('[BOG] createOrder failed HTTP ' . ($res['code'] ?? 0) . ': ' . json_encode($res['data'] ?? []));
            return null;
        }

        return [
            'bog_order_id' => $res['data']['id'],
            'redirect_url' => $res['data']['_links']['redirect']['href'] ?? '',
        ];
    }

    // ── Get payment details ───────────────────────────────────────────────────

    public function getReceipt(string $bogOrderId): ?array
    {
        $token = $this->getToken();
        if (!$token) return null;

        $res = $this->http('GET', $this->receiptUrl() . urlencode($bogOrderId), [
            'token' => $token,
        ]);

        return ($res && $res['code'] === 200) ? $res['data'] : null;
    }

    // ── Refund ────────────────────────────────────────────────────────────────

    /**
     * Refund a completed payment.
     * @param  float|null $amount  null = full refund, float = partial
     * @return array{key:string, message:string, action_id:string}|null
     */
    public function refund(string $bogOrderId, ?float $amount = null): ?array
    {
        $token = $this->getToken();
        if (!$token) return null;

        $body = $amount !== null ? ['amount' => round($amount, 2)] : [];

        $res = $this->http('POST', $this->refundUrl() . urlencode($bogOrderId), [
            'token' => $token,
            'json'  => $body,
        ]);

        if (!$res || !in_array($res['code'], [200, 201, 202], true)) {
            error_log('[BOG] refund failed HTTP ' . ($res['code'] ?? 0));
            return null;
        }

        return $res['data'];
    }

    // ── Preauthorization: approve ─────────────────────────────────────────────

    /**
     * Approve a preauthorized (blocked) payment.
     * @param  float|null $amount      null = full amount, float = partial
     * @param  string     $description Reason note
     */
    public function preAuthApprove(string $bogOrderId, ?float $amount = null, string $description = ''): ?array
    {
        $token = $this->getToken();
        if (!$token) return null;

        $body = [];
        if ($amount !== null)     $body['amount']      = (string) round($amount, 2);
        if ($description !== '')  $body['description'] = $description;

        $res = $this->http('POST', $this->preAuthApproveUrl() . urlencode($bogOrderId), [
            'token' => $token,
            'json'  => $body,
        ]);

        return ($res && in_array($res['code'], [200, 201, 202], true)) ? $res['data'] : null;
    }

    // ── Preauthorization: cancel ──────────────────────────────────────────────

    public function preAuthCancel(string $bogOrderId, string $description = ''): ?array
    {
        $token = $this->getToken();
        if (!$token) return null;

        $body = $description !== '' ? ['description' => $description] : [];

        $res = $this->http('POST', $this->preAuthCancelUrl() . urlencode($bogOrderId), [
            'token' => $token,
            'json'  => $body,
        ]);

        return ($res && in_array($res['code'], [200, 201, 202], true)) ? $res['data'] : null;
    }

    // ── Callback signature verification ───────────────────────────────────────

    /**
     * Verify the Callback-Signature header BEFORE json_decode (field order matters).
     */
    public function verifySignature(string $rawBody, string $signatureHeader): bool
    {
        if ($signatureHeader === '') return false;

        $sig    = base64_decode($signatureHeader, true);
        if ($sig === false) return false;

        $pubKey = openssl_pkey_get_public($this->activePublicKey());
        if (!$pubKey) return false;

        return openssl_verify($rawBody, $sig, $pubKey, OPENSSL_ALGO_SHA256) === 1;
    }

    // ── Status mapping ────────────────────────────────────────────────────────

    /** Map BOG order_status.key → GoniStore order status. */
    public function mapStatus(string $bogStatus): string
    {
        return match ($bogStatus) {
            'completed'          => 'processing',
            'rejected'           => 'failed',
            'refunded'           => 'refunded',
            'refunded_partially' => 'refunded',
            'blocked'            => 'pending',   // preauth hold
            default              => 'pending',
        };
    }

    /** Human-readable label for a BOG payment code. */
    public function codeLabel(string $code): string
    {
        return self::RESPONSE_CODES[$code] ?? 'Code ' . $code;
    }

    // ── Transaction store ─────────────────────────────────────────────────────

    public function txCreate(array $data): void
    {
        if (!$this->tableExists()) return;
        $this->qb->table('bog_transactions')->insert($data);
    }

    public function txFindByBogId(string $bogOrderId): ?array
    {
        if (!$this->tableExists()) return null;
        return $this->qb->table('bog_transactions')
            ->where('bog_order_id', '=', $bogOrderId)
            ->first();
    }

    public function txFindByExternalId(string $externalOrderId): ?array
    {
        if (!$this->tableExists()) return null;
        return $this->qb->table('bog_transactions')
            ->where('external_order_id', '=', $externalOrderId)
            ->first();
    }

    public function txUpdate(string $bogOrderId, array $data): void
    {
        if (!$this->tableExists()) return;
        $this->qb->table('bog_transactions')
            ->where('bog_order_id', '=', $bogOrderId)
            ->update($data);
    }

    /** @return array{items: array, total: int, pages: int} */
    public function txList(int $page = 1, int $perPage = 25): array
    {
        if (!$this->tableExists()) return ['items' => [], 'total' => 0, 'pages' => 0];

        $total  = (int) ($this->qb->table('bog_transactions')->count() ?? 0);
        $offset = ($page - 1) * $perPage;
        $items  = $this->qb->table('bog_transactions')
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        return [
            'items' => $items ?: [],
            'total' => $total,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    // ── HTTP helper ───────────────────────────────────────────────────────────

    /**
     * @return array{code:int, data:array}|null
     */
    private function http(string $method, string $url, array $opts = []): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            error_log('[BOG] curl_init() failed for: ' . $url);
            return null;
        }

        $headers = $opts['headers'] ?? [];

        if (!empty($opts['token'])) {
            $headers[] = 'Authorization: Bearer ' . $opts['token'];
        }
        if (!empty($opts['lang'])) {
            $headers[] = 'Accept-Language: ' . $opts['lang'];
        }

        $curlOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        // Auto-detect CA bundle (fixes SSL errors on XAMPP / Windows dev environments)
        $caBundle = $this->findCaBundle();
        if ($caBundle) {
            $curlOpts[CURLOPT_CAINFO] = $caBundle;
        }

        if (!empty($opts['basic_auth'])) {
            $curlOpts[CURLOPT_USERPWD] = $opts['basic_auth'];
        }

        if ($method === 'POST') {
            $curlOpts[CURLOPT_POST] = true;
            if (isset($opts['json'])) {
                $curlOpts[CURLOPT_POSTFIELDS] = json_encode($opts['json']);
                $curlOpts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
            } elseif (isset($opts['body'])) {
                $curlOpts[CURLOPT_POSTFIELDS] = $opts['body'];
            } else {
                $curlOpts[CURLOPT_POSTFIELDS] = '';
            }
        }

        curl_setopt_array($ch, $curlOpts);
        $body      = curl_exec($ch);
        $code      = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            error_log('[BOG] curl request failed: ' . $curlError . ' | url: ' . $url . ' | ca: ' . ($caBundle ?: 'none'));
            return null;
        }

        $data = json_decode((string) $body, true) ?? [];
        return ['code' => $code, 'data' => $data];
    }

    /**
     * Find a valid CA bundle for SSL verification.
     * Checks php.ini settings and common paths (XAMPP, Linux, macOS).
     */
    private function findCaBundle(): string
    {
        $candidates = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            'C:\\xampp\\php\\extras\\ssl\\cacert.pem',
            'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/usr/local/etc/openssl/cert.pem',
            '/usr/local/etc/openssl@3/cert.pem',
        ];

        foreach ($candidates as $path) {
            if ($path && is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    private function tableExists(): bool
    {
        try {
            $r = $this->qb->table('bog_transactions')->limit(0)->get();
            return $r !== null;
        } catch (\Throwable) {
            return false;
        }
    }
}
