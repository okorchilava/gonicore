<?php

declare(strict_types=1);

namespace GoniTotp;

/**
 * RFC 6238 TOTP implementation — no external dependencies.
 * Compatible with Google Authenticator, Authy, and any RFC 6238 app.
 */
final class TotpService
{
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const STEP   = 30;
    private const DIGITS = 6;

    /** Generate a new 160-bit base32-encoded secret. */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /** Build the otpauth:// URI for QR code rendering. */
    public function getOtpauthUrl(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($label)
            . '?secret='    . rawurlencode($secret)
            . '&issuer='    . rawurlencode($issuer)
            . '&digits='    . self::DIGITS
            . '&period='    . self::STEP
            . '&algorithm=SHA1';
    }

    /**
     * Verify a 6-digit TOTP code.
     * Accepts current window ± $window steps (default ±1 = ±30 s drift tolerance).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (strlen($code) !== self::DIGITS || !ctype_digit($code)) {
            return false;
        }

        $key = $this->base32Decode($secret);
        $t   = (int) floor(time() / self::STEP);

        for ($i = -$window; $i <= $window; $i++) {
            if ($this->hotp($key, $t + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function hotp(string $key, int $counter): string
    {
        // 8-byte big-endian counter (upper 32 bits are always 0 for TOTP)
        $msg  = pack('N*', 0, $counter);
        $hash = hash_hmac('sha1', $msg, $key, true);
        $off  = ord($hash[19]) & 0x0F;
        $otp  = (
            ((ord($hash[$off])     & 0x7F) << 24) |
            ((ord($hash[$off + 1]) & 0xFF) << 16) |
            ((ord($hash[$off + 2]) & 0xFF) << 8)  |
            ( ord($hash[$off + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $out    = '';
        $buffer = 0;
        $bits   = 0;

        foreach (str_split($data) as $c) {
            $buffer = ($buffer << 8) | ord($c);
            $bits  += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $out  .= self::BASE32[($buffer >> $bits) & 0x1F];
            }
        }

        if ($bits > 0) {
            $out .= self::BASE32[($buffer << (5 - $bits)) & 0x1F];
        }

        return $out;
    }

    private function base32Decode(string $data): string
    {
        $data   = strtoupper((string) preg_replace('/[\s=]/', '', $data));
        $out    = '';
        $buffer = 0;
        $bits   = 0;

        foreach (str_split($data) as $c) {
            $pos = strpos(self::BASE32, $c);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bits  += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out  .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $out;
    }
}
