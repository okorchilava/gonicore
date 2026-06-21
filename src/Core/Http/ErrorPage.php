<?php

declare(strict_types=1);

namespace GoniCore\Core\Http;

use Throwable;

/**
 * Renders a polished, self-contained HTML error page.
 *
 * CRITICAL: this renderer has ZERO runtime dependencies — no database, no
 * container, no theme, no LanguageService. It is the last line of defence and
 * is shown precisely when those things may be broken (e.g. the DB is down, as
 * with a missing table). Everything — CSS, icon, copy — is inlined, so it
 * renders no matter what else has failed.
 */
final class ErrorPage
{
    /**
     * Build the full HTML document for an error response.
     *
     * @param int            $status      HTTP status code (404, 403, 500, …).
     * @param string|null    $message     Human message (null → localized default).
     * @param Throwable|null $e           Exception — details shown only when $showDetails.
     * @param bool           $showDetails Show exception class / file / line / trace
     *                                    (caller passes true ONLY for authenticated admins).
     * @param string         $basePath    Base path for the "home" link (e.g. /goni/GoniCore).
     */
    public static function render(
        int $status,
        ?string $message = null,
        ?Throwable $e = null,
        bool $showDetails = false,
        string $basePath = '',
    ): string {
        $lang = (($_COOKIE['gc_lang'] ?? '') === 'en') ? 'en' : 'ka';
        $c    = self::copy($lang, $status);

        $home    = ($basePath !== '' ? rtrim($basePath, '/') : '') . '/';
        $message = ($message !== null && $message !== '') ? $message : $c['message'];

        $h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        // ── Technical details — shown ONLY to authenticated admins (the caller
        //    decides via $showDetails), and collapsed by default. Anonymous and
        //    non-admin visitors never see exception internals.
        $debugHtml = '';
        if ($showDetails && $e !== null) {
            $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI'));
            $uri    = (string) ($_SERVER['REQUEST_URI'] ?? '');
            $meta   =
                '<div class="err-meta">'
                . '<span class="err-exc">' . $h($e::class) . '</span>'
                . '<span class="err-loc">' . $h($e->getFile()) . ':' . $e->getLine() . '</span>'
                . ($uri !== '' ? '<span class="err-req">' . $h($method . ' ' . $uri) . '</span>' : '')
                . '</div>';
            $debugHtml =
                '<details class="err-details">'
                . '<summary>' . $h($c['details']) . '</summary>'
                . $meta
                . '<pre class="err-trace">' . $h($e->getTraceAsString()) . '</pre>'
                . '</details>';
        }

        $icon       = self::icon($status);
        $statusEsc  = $h((string) $status);
        $titleEsc   = $h($c['title']);
        $messageEsc = $h($message);
        $homeEsc    = $h($home);
        $brandEsc   = $h($c['brand']);
        $homeLbl    = $h($c['home']);
        $reloadLbl  = $h($c['reload']);
        $langAttr   = $lang === 'en' ? 'en' : 'ka';

        return <<<HTML
<!DOCTYPE html>
<html lang="{$langAttr}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{$statusEsc} · {$titleEsc}</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{
    font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif;
    background:#f1f5f9;color:#0f172a;min-height:100vh;
    display:flex;align-items:center;justify-content:center;padding:24px;line-height:1.55;
    -webkit-font-smoothing:antialiased;
  }
  .err-card{
    width:100%;max-width:560px;background:#fff;border:1px solid #e2e8f0;border-radius:20px;
    box-shadow:0 18px 48px -16px rgba(15,23,42,.18);padding:44px 40px;text-align:center;
  }
  .err-icon{
    width:72px;height:72px;margin:0 auto 22px;border-radius:18px;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#eef2ff,#e0e7ff);color:#4f46e5;
  }
  .err-icon svg{width:38px;height:38px}
  .err-code{
    font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
    color:#6366f1;margin-bottom:8px;
  }
  .err-title{font-size:26px;font-weight:800;letter-spacing:-.02em;margin-bottom:12px}
  .err-message{font-size:15.5px;color:#475569;margin:0 auto 28px;max-width:42ch}
  .err-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
  .err-btn{
    display:inline-flex;align-items:center;gap:7px;padding:11px 20px;border-radius:10px;
    font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;border:1px solid transparent;
    transition:transform .12s ease,box-shadow .12s ease,background .12s ease;
  }
  .err-btn:active{transform:translateY(1px)}
  .err-btn.primary{background:#4f46e5;color:#fff;box-shadow:0 8px 18px -6px rgba(79,70,229,.55)}
  .err-btn.primary:hover{background:#4338ca}
  .err-btn.ghost{background:#fff;color:#475569;border-color:#e2e8f0}
  .err-btn.ghost:hover{background:#f8fafc;color:#0f172a}
  .err-btn svg{width:17px;height:17px}
  .err-details{
    margin-top:30px;text-align:left;border:1px solid #e2e8f0;border-radius:12px;
    background:#f8fafc;overflow:hidden;
  }
  .err-details summary{
    cursor:pointer;padding:12px 16px;font-size:13px;font-weight:700;color:#b91c1c;
    list-style:none;user-select:none;
  }
  .err-details summary::-webkit-details-marker{display:none}
  .err-meta{padding:0 16px 12px;display:flex;flex-direction:column;gap:4px;font-size:12.5px}
  .err-exc{font-weight:700;color:#dc2626;word-break:break-word}
  .err-loc,.err-req{color:#64748b;font-family:ui-monospace,"SF Mono","Fira Code",Menlo,Consolas,monospace;word-break:break-all}
  .err-trace{
    margin:0;padding:14px 16px;border-top:1px solid #e2e8f0;background:#0f172a;color:#cbd5e1;
    font-family:ui-monospace,"SF Mono","Fira Code",Menlo,Consolas,monospace;font-size:11.5px;
    line-height:1.6;white-space:pre-wrap;word-break:break-word;max-height:340px;overflow:auto;
  }
  .err-brand{
    margin-top:30px;font-size:12px;color:#94a3b8;display:flex;align-items:center;
    justify-content:center;gap:6px;font-weight:600;letter-spacing:.3px;
  }
  .err-brand b{color:#4f46e5}
  @media (prefers-color-scheme:dark){
    body{background:#0b1120;color:#e2e8f0}
    .err-card{background:#111827;border-color:#1f2937;box-shadow:0 18px 48px -16px rgba(0,0,0,.6)}
    .err-message{color:#94a3b8}
    .err-icon{background:linear-gradient(135deg,#1e1b4b,#312e81);color:#a5b4fc}
    .err-btn.ghost{background:#0b1120;color:#cbd5e1;border-color:#1f2937}
    .err-btn.ghost:hover{background:#0f172a;color:#fff}
    .err-details{background:#0b1120;border-color:#1f2937}
    .err-meta .err-loc,.err-meta .err-req{color:#94a3b8}
  }
</style>
</head>
<body>
  <main class="err-card">
    <div class="err-icon">{$icon}</div>
    <div class="err-code">{$brandEsc} {$statusEsc}</div>
    <h1 class="err-title">{$titleEsc}</h1>
    <p class="err-message">{$messageEsc}</p>
    <div class="err-actions">
      <a class="err-btn primary" href="{$homeEsc}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10"/></svg>
        {$homeLbl}
      </a>
      <a class="err-btn ghost" href="javascript:location.reload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
        {$reloadLbl}
      </a>
    </div>
    {$debugHtml}
    <div class="err-brand">Powered by <b>GoniCore</b></div>
  </main>
</body>
</html>
HTML;
    }

    /**
     * Status-specific copy in the requested language.
     *
     * @return array<string,string>
     */
    private static function copy(string $lang, int $status): array
    {
        $ka = [
            400 => ['title' => 'არასწორი მოთხოვნა',     'message' => 'მოთხოვნა ვერ დამუშავდა.'],
            401 => ['title' => 'ავტორიზაცია საჭიროა',   'message' => 'გასაგრძელებლად გთხოვთ გაიაროთ ავტორიზაცია.'],
            403 => ['title' => 'წვდომა აკრძალულია',      'message' => 'ამ გვერდის ნახვის უფლება არ გაქვთ.'],
            404 => ['title' => 'გვერდი ვერ მოიძებნა',    'message' => 'მოთხოვნილი გვერდი არ არსებობს ან გადატანილია.'],
            405 => ['title' => 'მეთოდი დაუშვებელია',     'message' => 'ეს მოქმედება ამ მისამართზე დაუშვებელია.'],
            422 => ['title' => 'ვალიდაციის შეცდომა',     'message' => 'შეყვანილი მონაცემები არასწორია.'],
            500 => ['title' => 'რაღაც ვერ მოხერხდა',     'message' => 'სერვერზე მოხდა შეცდომა. ჩვენ უკვე ვმუშაობთ მის აღმოსაფხვრელად.'],
            503 => ['title' => 'სერვისი მიუწვდომელია',   'message' => 'საიტი დროებით მიუწვდომელია. სცადეთ მოგვიანებით.'],
        ];
        $en = [
            400 => ['title' => 'Bad request',            'message' => 'The request could not be processed.'],
            401 => ['title' => 'Authentication required', 'message' => 'Please sign in to continue.'],
            403 => ['title' => 'Access denied',          'message' => 'You don’t have permission to view this page.'],
            404 => ['title' => 'Page not found',         'message' => 'The page you requested does not exist or was moved.'],
            405 => ['title' => 'Method not allowed',     'message' => 'This action isn’t allowed on this URL.'],
            422 => ['title' => 'Validation error',       'message' => 'The submitted data was invalid.'],
            500 => ['title' => 'Something went wrong',   'message' => 'A server error occurred. We’re already looking into it.'],
            503 => ['title' => 'Service unavailable',    'message' => 'The site is temporarily unavailable. Please try again later.'],
        ];

        $map  = $lang === 'en' ? $en : $ka;
        $base = $map[$status] ?? ($status >= 500 ? $map[500] : $map[400]);

        $labels = $lang === 'en'
            ? ['home' => 'Back to home', 'reload' => 'Reload', 'details' => 'Technical details', 'brand' => 'Error']
            : ['home' => 'მთავარ გვერდზე', 'reload' => 'თავიდან ცდა', 'details' => 'ტექნიკური დეტალები', 'brand' => 'შეცდომა'];

        return $base + $labels;
    }

    /** Inline SVG icon chosen by status family. */
    private static function icon(int $status): string
    {
        // 401/403 → lock, 404 → magnifier, everything else → warning triangle.
        if ($status === 401 || $status === 403) {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
        }
        if ($status === 404) {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>';
        }
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    }
}
