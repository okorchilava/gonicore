<?php
/**
 * GCSMS — Inbound replies (received via the inbound webhook).
 * Vars: $items, $total, $pages, $page, $base, $sms
 */
$pageTitle = 'GCSMS — შემომავალი';
$activeNav = 'gcsms-inbound';

ob_start(); ?>
<?php if (!empty($items)): ?>
<form method="POST" action="<?= e($base) ?>/manage/gcsms/inbound/clear" style="display:inline-flex">
    <button type="button" class="topbar-btn" style="border-color:#ef4444;color:#ef4444"
        onclick="gcConfirm(this,'დარწმუნებული ხართ?','ყველა შემომავალი პასუხი სამუდამოდ წაიშლება.','გასუფთავება')">
        <span class="material-symbols-outlined mi-sm">delete</span> გასუფთავება
    </button>
</form>
<?php endif ?>
<?php $topbarActions = ob_get_clean(); ?>

<div class="card" style="overflow:hidden">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <h3>შემომავალი პასუხები</h3>
        <span style="font-size:13px;color:var(--muted)"><?= (int) $total ?> სულ</span>
    </div>

    <?php if (empty($items)): ?>
    <div class="card-body">
        <div style="text-align:center;padding:48px;color:var(--muted)">
            <div style="font-size:40px;margin-bottom:8px">📭</div>
            <p>ჯერ არცერთი შემომავალი პასუხი არ მიგიღიათ.</p>
            <p style="font-size:12.5px;margin-top:8px">
                დარწმუნდით, რომ gosms.ge-ის პანელში მითითებულია Inbound webhook URL
                (იხ. <a href="<?= e($base) ?>/manage/gcsms/settings">Settings</a>).
            </p>
        </div>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>გამომგზავნი</th>
                    <th>მიმღები</th>
                    <th>ტექსტი</th>
                    <th>NoSMS</th>
                    <th>გაგზავნის დრო</th>
                    <th>მიღებული</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $m): ?>
                <tr>
                    <td style="white-space:nowrap;font-weight:600">
                        <?= e((string) ($m['from_number'] ?? '')) ?>
                        <?php if ((int) ($m['is_read'] ?? 1) === 0): ?>
                        <span style="background:#10B27C;color:#fff;font-size:9px;font-weight:700;border-radius:9px;padding:1px 6px;margin-left:4px">ახალი</span>
                        <?php endif ?>
                    </td>
                    <td style="white-space:nowrap"><?= e((string) ($m['to_number'] ?? '')) ?></td>
                    <td><?= e((string) ($m['message'] ?? '')) ?></td>
                    <td><?= ((int) ($m['no_sms'] ?? 0) === 1) ? '<span style="color:#d97706;font-weight:700">NO SMS</span>' : '<span style="color:var(--muted)">—</span>' ?></td>
                    <td style="white-space:nowrap;color:var(--muted);font-size:12.5px"><?= e((string) ($m['sent_at'] ?? '')) ?: '—' ?></td>
                    <td style="white-space:nowrap;color:var(--muted);font-size:12.5px"><?= e((string) ($m['created_at'] ?? '')) ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<?php if (($pages ?? 1) > 1): ?>
<div class="pagination" style="margin-top:16px">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="<?= e($base) ?>/manage/gcsms/inbound?page=<?= $i ?>" class="<?= $i === ($page ?? 1) ? 'current' : '' ?>"><?= $i ?></a>
    <?php endfor ?>
</div>
<?php endif ?>
