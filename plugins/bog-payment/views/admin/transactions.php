<?php
$pageTitle     = 'BOG Transactions';
$activeNav     = 'bog-transactions';
$sandboxBadge  = $bog->isSandbox()
    ? '<span style="background:#f59e0b;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px">SANDBOX</span>'
    : '';
$topbarActions = $sandboxBadge . '<a href="' . e($base) . '/manage/store/bog-settings" class="topbar-btn ghost">⚙ Settings</a>';

$statusColors = [
    'created'            => ['#f59e0b', '#fef3c7'],
    'processing'         => ['#0ea5e9', '#e0f2fe'],
    'completed'          => ['#10b981', '#dcfce7'],
    'rejected'           => ['#ef4444', '#fef2f2'],
    'refunded'           => ['#8b5cf6', '#ede9fe'],
    'refunded_partially' => ['#8b5cf6', '#ede9fe'],
    'blocked'            => ['#f97316', '#fff7ed'],
    'pending'            => ['#94a3b8', '#f1f5f9'],
];
?>

<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>All Transactions</h3>
        <span style="font-size:13px;color:var(--muted)"><?= number_format($total ?? 0) ?> total</span>
    </div>

    <?php if (empty($items)): ?>
    <div class="empty">
        <div class="empty-icon">🏦</div>
        <h3>No transactions yet</h3>
        <p>BOG payments will appear here after the first transaction.</p>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>BOG Order ID</th>
                    <th>Ext. / Order #</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Code</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $tx):
                $colors = $statusColors[$tx['status']] ?? ['#94a3b8','#f1f5f9'];
            ?>
            <tr>
                <td style="font-family:monospace;font-size:12px"><?= e(substr((string)$tx['bog_order_id'],0,20)) ?>…</td>
                <td>
                    <?php if (!empty($tx['external_order_id']) && is_numeric($tx['external_order_id'])): ?>
                    <a href="<?= e($base) ?>/manage/store/orders/<?= (int)$tx['external_order_id'] ?>">#<?= (int)$tx['external_order_id'] ?></a>
                    <?php else: ?>
                    <span style="color:var(--muted)"><?= e((string)($tx['external_order_id'] ?? '—')) ?></span>
                    <?php endif ?>
                </td>
                <td style="font-weight:700"><?= number_format((float)$tx['amount'],2) ?> <?= e((string)$tx['currency']) ?></td>
                <td><?= e((string)($tx['payment_method'] ?: '—')) ?></td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $colors[1] ?>;color:<?= $colors[0] ?>">
                        <?= e((string)$tx['status']) ?>
                    </span>
                </td>
                <td style="font-size:12px">
                    <?php if (!empty($tx['payment_code'])): ?>
                    <span title="<?= e($bog->codeLabel((string)$tx['payment_code'])) ?>">
                        <?= e((string)$tx['payment_code']) ?>
                    </span>
                    <?php else: ?>—<?php endif ?>
                </td>
                <td style="font-size:12px;color:var(--muted)"><?= e(date('M j, Y H:i', strtotime((string)$tx['created_at']))) ?></td>
                <td>
                    <a href="<?= e($base) ?>/manage/store/bog-transactions/<?= urlencode((string)$tx['bog_order_id']) ?>" class="btn btn-ghost" style="font-size:11px;padding:4px 10px">View</a>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (($pages ?? 1) > 1): ?>
    <div class="pagination" style="padding:16px 20px">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= $i === ($page ?? 1) ? 'current' : '' ?>"><?= $i ?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
