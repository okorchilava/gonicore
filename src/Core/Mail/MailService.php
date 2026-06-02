<?php

declare(strict_types=1);

namespace GoniCore\Core\Mail;

use GoniCore\Modules\Settings\SettingsService;

/**
 * Sends HTML emails via PHP mail() or SMTP.
 *
 * Plugins and core code trigger notifications through the hook:
 *   $hooks->doAction('admin.notify', string $subject, string $htmlBody)
 *
 * Or call directly:
 *   $mailer->adminNotify($subject, $html)
 *   $mailer->send($to, $subject, $html)
 */
final class MailService
{
    public function __construct(private readonly SettingsService $settings) {}

    // ── Public API ────────────────────────────────────────────────────────────

    public function send(string $to, string $subject, string $html, ?string $fromAddress = null, ?string $fromName = null): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

        $from     = $fromAddress ?? (string) $this->settings->get('mail_from_address', '');
        $fromName = $fromName    ?? (string) $this->settings->get('mail_from_name',    'GoniCore');
        $driver   = (string) $this->settings->get('mail_driver', 'php');

        if ($from === '') {
            $host = (string) $this->settings->get('mail_smtp_host', 'localhost');
            $from = 'noreply@' . ($host ?: 'localhost');
        }

        return $driver === 'smtp'
            ? $this->sendSmtp($to, $subject, $html, $from, $fromName)
            : $this->sendPhpMail($to, $subject, $html, $from, $fromName);
    }

    /** Send to the configured admin email. */
    public function adminNotify(string $subject, string $html): bool
    {
        $adminEmail = (string) $this->settings->get('admin_email', '');
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) return false;
        return $this->send($adminEmail, $subject, $html);
    }

    /**
     * Build a branded HTML email body.
     *
     * @param string      $heading  Bold title at top of email
     * @param string      $body     HTML content (paragraphs, lists, etc.)
     * @param string|null $ctaUrl   Optional call-to-action button URL
     * @param string|null $ctaText  Optional call-to-action button label
     */
    public function template(string $heading, string $body, ?string $ctaUrl = null, ?string $ctaText = null): string
    {
        $siteName = e($this->settings->siteName() ?: 'GoniCore');
        $year     = date('Y');
        $cta      = '';
        if ($ctaUrl && $ctaText) {
            $cta = '<p style="text-align:center;margin:32px 0 0">
                      <a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES) . '"
                         style="display:inline-block;background:#10B27C;color:#fff;padding:12px 28px;border-radius:8px;font-weight:700;font-size:14px;text-decoration:none">'
                     . htmlspecialchars($ctaText, ENT_QUOTES)
                     . '</a></p>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="ka">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:system-ui,-apple-system,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 16px">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">
      <!-- Header -->
      <tr><td style="background:#0f172a;border-radius:12px 12px 0 0;padding:24px 32px;text-align:center">
        <span style="font-size:22px;font-weight:900;color:#f8fafc">Goni</span><span style="font-size:22px;font-weight:300;color:#10B27C">Core</span>
      </td></tr>
      <!-- Body -->
      <tr><td style="background:#ffffff;padding:36px 32px;border-radius:0 0 12px 12px">
        <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0 0 20px;line-height:1.3">{$heading}</h1>
        <div style="font-size:15px;color:#334155;line-height:1.7">{$body}</div>
        {$cta}
      </td></tr>
      <!-- Footer -->
      <tr><td style="padding:20px 0;text-align:center;font-size:12px;color:#94a3b8">
        &copy; {$year} {$siteName} &mdash; Admin Notification
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    // ── Drivers ───────────────────────────────────────────────────────────────

    private function sendPhpMail(string $to, string $subject, string $html, string $from, string $fromName): bool
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
        $headers .= "X-Mailer: GoniCore\r\n";

        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
    }

    private function sendSmtp(string $to, string $subject, string $html, string $from, string $fromName): bool
    {
        $host = (string) $this->settings->get('mail_smtp_host', 'localhost');
        $port = (int)    $this->settings->get('mail_smtp_port', 587);
        $user = (string) $this->settings->get('mail_smtp_user', '');
        $pass = (string) $this->settings->get('mail_smtp_pass', '');
        $enc  = (string) $this->settings->get('mail_smtp_encryption', 'tls');

        $transport = ($enc === 'ssl') ? "ssl://{$host}" : "tcp://{$host}";

        try {
            $ctx  = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $sock = @stream_socket_client("{$transport}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
            if (!$sock) return false;

            $read = fn() => fgets($sock, 515);
            $send = function (string $cmd) use ($sock, $read): string {
                fwrite($sock, $cmd . "\r\n");
                return $read();
            };

            $read(); // greeting
            $send("EHLO " . gethostname());
            if ($enc === 'tls') {
                $send("STARTTLS");
                stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $send("EHLO " . gethostname());
            }
            if ($user !== '') {
                $send("AUTH LOGIN");
                $send(base64_encode($user));
                $send(base64_encode($pass));
            }
            $send("MAIL FROM:<{$from}>");
            $send("RCPT TO:<{$to}>");
            $send("DATA");

            $boundary = md5(uniqid());
            $message  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n"
                      . "To: {$to}\r\n"
                      . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
                      . "MIME-Version: 1.0\r\n"
                      . "Content-Type: text/html; charset=UTF-8\r\n"
                      . "Content-Transfer-Encoding: base64\r\n\r\n"
                      . chunk_split(base64_encode($html));

            fwrite($sock, $message . "\r\n.\r\n");
            $read();
            $send("QUIT");
            fclose($sock);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
