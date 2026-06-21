<?php

declare(strict_types=1);

namespace BogPayment;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

/**
 * BOG Payment Gateway — web controller.
 *
 * Public routes (no auth):
 *   GET  /bog/success        — user returns after successful payment
 *   GET  /bog/fail           — user returns after failed payment
 *   POST /bog/callback       — async webhook from BOG (RSA-signed)
 *   POST /bog/pay            — shortcode / standalone payment initiation
 *
 * Admin routes (requires login):
 *   GET  /manage/store/bog-settings              — settings form
 *   POST /manage/store/bog-settings              — save settings
 *   GET  /manage/store/bog-transactions          — transactions list
 *   GET  /manage/store/bog-transactions/{id}     — transaction detail
 *   POST /manage/store/bog-transactions/{id}/refund    — refund action
 *   POST /manage/store/bog-transactions/{id}/approve   — preauth approve
 *   POST /manage/store/bog-transactions/{id}/cancel    — preauth cancel
 */
final class BogController
{
    public function __construct(
        private readonly BogService   $bog,
        private readonly QueryBuilder $qb,
        private readonly LoginService $auth,
        private readonly HookManager  $hooks,
        private readonly string       $siteName = 'GoniCore',
    ) {}

    // ── Standalone payment (shortcode) ────────────────────────────────────────

    public function pay(Request $request): Response
    {
        $amount      = (float)  ($request->post('amount')      ?? 0);
        $currency    = strtoupper(trim((string) ($request->post('currency')    ?? 'GEL')));
        $description = trim((string) ($request->post('description') ?? ''));
        $externalId  = trim((string) ($request->post('order_id')    ?? 'CUSTOM-' . time()));
        $successUrl  = trim((string) ($request->post('success')     ?? ''));
        $failUrl     = trim((string) ($request->post('fail')        ?? ''));

        if ($amount <= 0) {
            return Response::redirect($request->basePath() . '/?bog_error=invalid_amount');
        }

        $base    = $request->basePath();
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $absBase = rtrim($scheme . '://' . $host . $base, '/');

        $callbackUrl = $absBase . '/bog/callback';
        $okUrl       = $successUrl ?: $absBase . '/bog/success';
        $noUrl       = $failUrl    ?: $absBase . '/bog/fail';

        $basket = [[
            'product_id'  => $externalId,
            'description' => $description ?: 'Payment',
            'quantity'    => 1,
            'unit_price'  => round($amount, 2),
            'total_price' => round($amount, 2),
        ]];

        $result = $this->bog->createOrder(
            externalOrderId: $externalId,
            total:           $amount,
            currency:        $currency,
            basket:          $basket,
            callbackUrl:     $callbackUrl,
            successUrl:      $okUrl,
            failUrl:         $noUrl,
            description:     $description,
        );

        if (!$result) {
            return Response::redirect($noUrl . '?error=payment_init_failed');
        }

        // Store transaction
        $this->bog->txCreate([
            'bog_order_id'      => $result['bog_order_id'],
            'external_order_id' => $externalId,
            'amount'            => round($amount, 2),
            'currency'          => $currency,
            'status'            => 'created',
            'description'       => $description,
        ]);

        return Response::redirect($result['redirect_url']);
    }

    // ── User return: success ──────────────────────────────────────────────────

    public function success(Request $request): Response
    {
        $bogOrderId = trim((string) ($request->query('order_id') ?? ''));

        if ($bogOrderId) {
            $details = $this->bog->getReceipt($bogOrderId);
            if ($details) {
                $this->processDetails($details, $request->basePath());
            }
        }

        // Payment confirmed — clear cart now that we're back in the user's browser session
        if (session_status() === PHP_SESSION_NONE) session_start();
        unset($_SESSION['gs_cart'], $_SESSION['gs_coupon']);

        $tx = $bogOrderId ? $this->bog->txFindByBogId($bogOrderId) : null;
        $internalId = $tx['external_order_id'] ?? '';

        // GoniStore order?
        if ($internalId && is_numeric($internalId)) {
            return Response::redirect($request->basePath() . '/shop/order-received/' . (int) $internalId);
        }

        return Response::redirect($request->basePath() . '/?bog_success=1');
    }

    // ── User return: fail ─────────────────────────────────────────────────────

    public function fail(Request $request): Response
    {
        $checkoutSlug = $this->bog->setting('checkout_slug', 'checkout');
        return Response::redirect(
            $request->basePath() . '/' . $checkoutSlug
            . '?error=' . urlencode('Payment was not completed. Please try again.')
        );
    }

    // ── Async callback from BOG ───────────────────────────────────────────────

    public function callback(Request $request): Response
    {
        $rawBody   = (string) file_get_contents('php://input');
        $sigHeader = (string) ($_SERVER['HTTP_CALLBACK_SIGNATURE'] ?? '');

        if (!$this->bog->verifySignature($rawBody, $sigHeader)) {
            error_log('[BOG] Callback signature invalid.');
            return Response::html('', 400);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || ($payload['event'] ?? '') !== 'order_payment') {
            return Response::html('', 200);
        }

        $body = $payload['body'] ?? [];
        $this->processDetails($body, $request->basePath(), $rawBody);

        return Response::html('', 200); // must respond 200
    }

    // ── Admin: transactions list ──────────────────────────────────────────────

    public function transactionsList(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $page = max(1, (int) ($request->query('page') ?? 1));
        $data = $this->bog->txList($page, 25);

        return $this->renderPage('admin/transactions', [
            'base'    => $request->basePath(),
            'items'   => $data['items'],
            'total'   => $data['total'],
            'pages'   => $data['pages'],
            'page'    => $page,
            'bog'     => $this->bog,
        ]);
    }

    // ── Admin: transaction detail ─────────────────────────────────────────────

    public function transactionDetail(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $bogOrderId = (string) $request->getAttribute('id');
        $tx         = $this->bog->txFindByBogId($bogOrderId);
        if (!$tx) {
            return Response::redirect($request->basePath() . '/manage/store/bog-transactions');
        }

        // Fetch live details from BOG
        $receipt = $this->bog->getReceipt($bogOrderId);

        return $this->renderPage('admin/transaction', [
            'base'    => $request->basePath(),
            'tx'      => $tx,
            'receipt' => $receipt,
            'bog'     => $this->bog,
            'flash'   => $request->query('msg') ?? '',
            'error'   => $request->query('err') ?? '',
        ]);
    }

    // ── Admin: refund ─────────────────────────────────────────────────────────

    public function refund(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $bogOrderId = (string) $request->getAttribute('id');
        $amount     = $request->post('amount') !== '' ? (float) $request->post('amount') : null;

        $result = $this->bog->refund($bogOrderId, $amount);
        if (!$result) {
            return Response::redirect($request->basePath() . '/manage/store/bog-transactions/' . urlencode($bogOrderId) . '?err=Refund+failed');
        }

        $this->bog->txUpdate($bogOrderId, [
            'status'    => 'refunded',
            'action_id' => $result['action_id'] ?? '',
        ]);

        return Response::redirect($request->basePath() . '/manage/store/bog-transactions/' . urlencode($bogOrderId) . '?msg=Refund+submitted');
    }

    // ── Admin: preauth approve ────────────────────────────────────────────────

    public function preAuthApprove(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $bogOrderId  = (string) $request->getAttribute('id');
        $amount      = $request->post('amount') !== '' ? (float) $request->post('amount') : null;
        $description = trim((string) ($request->post('description') ?? ''));

        $result = $this->bog->preAuthApprove($bogOrderId, $amount, $description);
        if (!$result) {
            return Response::redirect($request->basePath() . '/manage/store/bog-transactions/' . urlencode($bogOrderId) . '?err=Preauth+approve+failed');
        }

        $this->bog->txUpdate($bogOrderId, [
            'status'    => 'completed',
            'action_id' => $result['action_id'] ?? '',
        ]);

        return Response::redirect($request->basePath() . '/manage/store/bog-transactions/' . urlencode($bogOrderId) . '?msg=Payment+approved');
    }

    // ── Admin: preauth cancel ─────────────────────────────────────────────────

    public function preAuthCancel(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $bogOrderId  = (string) $request->getAttribute('id');
        $description = trim((string) ($request->post('description') ?? ''));

        $result = $this->bog->preAuthCancel($bogOrderId, $description);
        if (!$result) {
            return Response::redirect($request->basePath() . '/manage/store/bog-transactions/' . urlencode($bogOrderId) . '?err=Preauth+cancel+failed');
        }

        $this->bog->txUpdate($bogOrderId, [
            'status'    => 'rejected',
            'action_id' => $result['action_id'] ?? '',
        ]);

        return Response::redirect($request->basePath() . '/manage/store/bog-transactions/' . urlencode($bogOrderId) . '?msg=Payment+cancelled');
    }

    // ── Admin: settings ───────────────────────────────────────────────────────

    public function settingsForm(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        return $this->renderPage('settings', [
            'base'  => $request->basePath(),
            'saved' => $request->query('saved') === '1',
            'bog'   => $this->bog,
        ]);
    }

    public function settingsSave(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        foreach (['enabled','sandbox','client_id','client_secret','currency','checkout_slug','capture','notify_admin'] as $f) {
            $this->bog->setSetting($f, trim((string) ($request->post($f) ?? '')));
        }

        // Reset both prod and sandbox token caches
        $this->bog->setSetting('token', '');
        $this->bog->setSetting('token_expires_at', '0');
        $this->bog->setSetting('token_sandbox', '');
        $this->bog->setSetting('token_sandbox_expires_at', '0');

        return Response::redirect($request->basePath() . '/manage/store/bog-settings?saved=1');
    }

    // ── Internal: process payment details ────────────────────────────────────

    private function processDetails(array $body, string $base, string $rawBody = ''): void
    {
        $bogOrderId      = (string) ($body['order_id']          ?? '');
        $externalOrderId = (string) ($body['external_order_id'] ?? '');
        $statusKey       = (string) ($body['order_status']['key']  ?? '');
        $amount          = (float)  ($body['purchase_units']['transfer_amount'] ?? $body['purchase_units']['request_amount'] ?? 0);
        $currency        = (string) ($body['purchase_units']['currency_code']   ?? 'GEL');
        $payMethod       = (string) ($body['payment_detail']['transfer_method']['key'] ?? '');
        $payCode         = (string) ($body['payment_detail']['code'] ?? '');
        $payerIdent      = (string) ($body['payment_detail']['payer_identifier'] ?? '');

        if (!$bogOrderId) return;

        // Upsert transaction record
        $tx = $this->bog->txFindByBogId($bogOrderId);
        if ($tx) {
            $this->bog->txUpdate($bogOrderId, [
                'status'           => $statusKey,
                'payment_method'   => $payMethod,
                'payment_code'     => $payCode,
                'payer_identifier' => $payerIdent,
                'raw_callback'     => $rawBody ?: null,
            ]);
        } else {
            $this->bog->txCreate([
                'bog_order_id'      => $bogOrderId,
                'external_order_id' => $externalOrderId,
                'amount'            => $amount,
                'currency'          => $currency,
                'status'            => $statusKey,
                'payment_method'    => $payMethod,
                'payment_code'      => $payCode,
                'payer_identifier'  => $payerIdent,
                'raw_callback'      => $rawBody ?: null,
            ]);
        }

        // ── GoniStore order automation ────────────────────────────────────────
        $storeOrderId = is_numeric($externalOrderId) ? (int) $externalOrderId : 0;
        if ($storeOrderId) {
            $storeStatus = $this->bog->mapStatus($statusKey);

            // Try to update gs_orders
            try {
                $update = ['status' => $storeStatus];
                if (empty($tx['transaction_id'] ?? '')) {
                    $update['transaction_id'] = $bogOrderId;
                }
                $this->qb->table('gs_orders')
                    ->where('id', '=', $storeOrderId)
                    ->update($update);

                // Add order note
                $this->qb->table('gs_order_notes')->insert([
                    'order_id'   => $storeOrderId,
                    'note'       => sprintf(
                        'BOG payment %s (BOG ID: %s, code: %s)',
                        $statusKey, $bogOrderId, $payCode
                    ),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                error_log('[BOG] GoniStore order update failed: ' . $e->getMessage());
            }
        }

        // ── Admin notification on success ─────────────────────────────────────
        if ($statusKey === 'completed' && $this->bog->setting('notify_admin', '1') !== '0') {
            $codeLabel  = $this->bog->codeLabel($payCode);
            $detailsUrl = rtrim($base, '/') . '/manage/store/bog-transactions/' . $bogOrderId;

            $html = "<p>A payment has been successfully processed via <strong>Bank of Georgia</strong>.</p>
                     <table style='width:100%;border-collapse:collapse;margin:16px 0;font-size:14px'>
                       <tr style='border-bottom:1px solid #e2e8f0'>
                         <td style='padding:8px 0;color:#64748b;width:150px'>BOG Order ID</td>
                         <td style='padding:8px 0;font-weight:600'>{$bogOrderId}</td>
                       </tr>
                       <tr style='border-bottom:1px solid #e2e8f0'>
                         <td style='padding:8px 0;color:#64748b'>Amount</td>
                         <td style='padding:8px 0;font-weight:600'>{$amount} {$currency}</td>
                       </tr>
                       <tr style='border-bottom:1px solid #e2e8f0'>
                         <td style='padding:8px 0;color:#64748b'>Method</td>
                         <td style='padding:8px 0'>{$payMethod}</td>
                       </tr>
                       <tr style='border-bottom:1px solid #e2e8f0'>
                         <td style='padding:8px 0;color:#64748b'>Response</td>
                         <td style='padding:8px 0'>{$codeLabel}</td>
                       </tr>"
                . ($storeOrderId ? "
                       <tr>
                         <td style='padding:8px 0;color:#64748b'>Order #</td>
                         <td style='padding:8px 0'>{$storeOrderId}</td>
                       </tr>" : '')
                . "</table>";

            $this->hooks->emit('admin.notify',
                'New BOG Payment Received — ' . $amount . ' ' . $currency,
                $html,
                $detailsUrl,
                'View Transaction'
            );
        }
    }

    // ── renderPage (manage layout wrapper) ───────────────────────────────────

    /** @param array<string, mixed> $data */
    private function renderPage(string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        $base     = $data['base'] ?? '';
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

        $notifList       = [];
        $notifUnread     = 0;
        $panelLangs      = [];
        $currentLangCode = 'en';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/' . $view . '.php';
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $themeDir . '/manage/layout.php';
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
