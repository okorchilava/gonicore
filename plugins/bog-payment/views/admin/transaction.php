<?php
$pageTitle     = 'Transaction — ' . e(substr((string)($tx['bog_order_id'] ?? ''), 0, 16)) . '…';
$activeNav     = 'bog-transactions';
$topbarActions = '<a href="' . e($base) . '/manage/store/bog-transactions" class="topbar-btn ghost">← Transactions</a>';

$isCompleted = ($tx['status'] ?? '') === 'completed';
$isBlocked   = ($tx['status'] ?? '') === 'blocked';  // preauth
$isRefunded  = in_array($tx['status'] ?? '', ['refunded', 'refunded_partially'], true);
?>

<?php if (!empty($flash)): ?>
<div id="gc-flash" data-msg="<?= e($flash) ?>" data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if (!empty($error)): ?>
<div id="gc-flash" data-msg="<?= e($error) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;max-width:1100px">

    <!-- Main info -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- IDs & Status -->
        <div class="card">
            <div class="card-header"><h3>Transaction Details</h3></div>
            <div class="card-body">
                <?php
                $rows = [
                    'BOG Order ID'   => '<code style="font-size:12px">' . e((string)$tx['bog_order_id']) . '</code>',
                    'External / Order' => !empty($tx['external_order_id']) && is_numeric($tx['external_order_id'])
                        ? '<a href="' . e($base) . '/manage/store/orders/' . (int)$tx['external_order_id'] . '">#' . (int)$tx['external_order_id'] . '</a>'
                        : e((string)($tx['external_order_id'] ?? '—')),
                    'Amount'         => '<strong>' . number_format((float)$tx['amount'],2) . ' ' . e((string)$tx['currency']) . '</strong>',
                    'Status'         => '<span class="badge ' . e((string)$tx['status']) . '">' . e((string)$tx['status']) . '</span>',
                    'Payment Method' => e((string)($tx['payment_method'] ?: '—')),
                    'Response Code'  => !empty($tx['payment_code'])
                        ? e((string)$tx['payment_code']) . ' — ' . e($bog->codeLabel((string)$tx['payment_code']))
                        : '—',
                    'Payer'          => e((string)($tx['payer_identifier'] ?: '—')),
                    'Description'    => e((string)($tx['description'] ?: '—')),
                    'Created'        => e(date('Y-m-d H:i:s', strtotime((string)$tx['created_at']))),
                    'Updated'        => e(date('Y-m-d H:i:s', strtotime((string)$tx['updated_at']))),
                ];
                ?>
                <table style="width:100%;border-collapse:collapse;font-size:13.5px">
                <?php foreach ($rows as $k => $v): ?>
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:10px 0;color:var(--muted);width:160px"><?= $k ?></td>
                    <td style="padding:10px 0"><?= $v ?></td>
                </tr>
                <?php endforeach ?>
                </table>
            </div>
        </div>

        <!-- Live receipt from BOG -->
        <?php if (!empty($receipt)): ?>
        <div class="card">
            <div class="card-header"><h3>Live Receipt from BOG</h3></div>
            <div class="card-body">
                <pre style="font-size:11px;background:var(--bg);padding:14px;border-radius:8px;overflow:auto;max-height:320px"><?= e(json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        </div>
        <?php endif ?>

        <!-- Raw callback -->
        <?php if (!empty($tx['raw_callback'])): ?>
        <div class="card">
            <div class="card-header"><h3>Raw Callback Payload</h3></div>
            <div class="card-body">
                <pre style="font-size:11px;background:var(--bg);padding:14px;border-radius:8px;overflow:auto;max-height:280px"><?= e(json_encode(json_decode((string)$tx['raw_callback'],true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        </div>
        <?php endif ?>

    </div>

    <!-- Actions sidebar -->
    <div style="display:flex;flex-direction:column;gap:14px">

        <?php if ($isCompleted && !$isRefunded): ?>
        <!-- Refund -->
        <div class="card">
            <div class="card-header"><h3>💸 Refund</h3></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:14px">Full or partial refund. Leave amount empty for full refund.</p>
                <form method="POST" action="<?= e($base) ?>/manage/store/bog-transactions/<?= urlencode((string)$tx['bog_order_id']) ?>/refund">
                    <div class="form-group">
                        <label class="form-label">Amount (optional)</label>
                        <input type="number" name="amount" class="form-input" step="0.01" min="0.01"
                               max="<?= (float)$tx['amount'] ?>" placeholder="Leave empty = full refund">
                    </div>
                    <button type="button" class="btn btn-danger" style="width:100%;justify-content:center"
                            onclick="gcConfirm(this,'Confirm Refund','This will initiate a refund via BOG.','Refund','#ef4444')">
                        Refund Payment
                    </button>
                </form>
            </div>
        </div>
        <?php endif ?>

        <?php if ($isBlocked): ?>
        <!-- Preauth: Approve -->
        <div class="card">
            <div class="card-header"><h3>✅ Approve Preauth</h3></div>
            <div class="card-body">
                <form method="POST" action="<?= e($base) ?>/manage/store/bog-transactions/<?= urlencode((string)$tx['bog_order_id']) ?>/approve">
                    <div class="form-group">
                        <label class="form-label">Amount (optional)</label>
                        <input type="number" name="amount" class="form-input" step="0.01" min="0.01"
                               max="<?= (float)$tx['amount'] ?>" placeholder="Leave empty = full amount">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Note (optional)</label>
                        <input type="text" name="description" class="form-input" placeholder="Approval reason">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Approve Payment</button>
                </form>
            </div>
        </div>
        <!-- Preauth: Cancel -->
        <div class="card">
            <div class="card-header"><h3>❌ Cancel Preauth</h3></div>
            <div class="card-body">
                <form method="POST" action="<?= e($base) ?>/manage/store/bog-transactions/<?= urlencode((string)$tx['bog_order_id']) ?>/cancel">
                    <div class="form-group">
                        <label class="form-label">Reason (optional)</label>
                        <input type="text" name="description" class="form-input" placeholder="Cancellation reason">
                    </div>
                    <button type="button" class="btn btn-danger" style="width:100%;justify-content:center"
                            onclick="gcConfirm(this,'Cancel Preauthorization','The held funds will be released.','Cancel','#ef4444')">
                        Cancel &amp; Release Funds
                    </button>
                </form>
            </div>
        </div>
        <?php endif ?>

        <!-- Sync from BOG -->
        <div class="card">
            <div class="card-header"><h3>🔄 Sync</h3></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:14px">Refresh transaction status from BOG API.</p>
                <a href="?sync=1" class="btn btn-ghost" style="width:100%;justify-content:center">Sync Status</a>
            </div>
        </div>

    </div>
</div>
