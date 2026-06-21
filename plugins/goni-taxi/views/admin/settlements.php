<?php
$pageTitle  = 'Settlement';
$activeNav  = 'taxi-settlements';
$topbarActions = '
<a href="'.e($base).'/manage/taxi/settlements/export-csv?from='.e($dateFrom).'&to='.e($dateTo).'" class="btn btn-ghost" style="font-size:13px">⬇ CSV</a>
<a href="'.e($base).'/manage/taxi/settlements/export-xml?from='.e($dateFrom).'&to='.e($dateTo).'" class="btn btn-primary" style="font-size:13px">📄 BOG XML</a>
';
$totalGross  = array_sum(array_column($calcData, 'gross_amount'));
$totalComm   = array_sum(array_column($calcData, 'commission'));
$totalPayout = array_sum(array_filter(array_column($calcData, 'net_amount'), fn($v) => $v > 0));
$totalDebt   = array_sum(array_filter(array_column($calcData, 'net_amount'), fn($v) => $v < 0));
?>
<?php if(($_GET['created']??'')==='1'): ?>
<div id="gc-flash" data-msg="Settlement records created." data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if(($_GET['updated']??'')==='1'): ?>
<div id="gc-flash" data-msg="Settlement updated." data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if(($_GET['reminder']??'')==='1'): ?>
<div id="gc-flash" data-msg="შეხსენება გაიგზავნა ადმინზე." data-icon="success" style="display:none"></div>
<?php endif ?>

<?php
// Reminder status bar
$reminderLastSent = $taxi->setting('settlement_reminder_last_sent','');
$todayIsReminderDay = in_array((int)date('j'), [1, 15]);
$nextReminderDay  = (int)date('j') < 15 ? date('Y-m-15') : date('Y-m-01', strtotime('+1 month'));
?>
<div style="display:flex;align-items:center;justify-content:space-between;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:12px 18px;margin-bottom:16px;gap:12px;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:20px">🔔</span>
        <div>
            <div style="font-size:13px;font-weight:700">ავტო-შეხსენება</div>
            <div style="font-size:12px;color:var(--muted)">
                ყოველი თვის 1 და 15 რიცხვში 12:00-ზე გაიგზავნება მეილი ·
                <?php if($reminderLastSent): ?>
                ბოლო გაგზავნა: <strong><?= e($reminderLastSent) ?></strong>
                <?php else: ?>
                ჯერ არ გაგზავნილა
                <?php endif ?>
            </div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <?php if($todayIsReminderDay): ?>
        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:#eff6ff;color:#3b82f6">📅 დღეს შეხსენების დღეა</span>
        <?php else: ?>
        <span style="font-size:11px;color:var(--muted)">შემდეგი: <?= $nextReminderDay ?></span>
        <?php endif ?>
        <form method="POST" action="<?= e($base)?>/manage/taxi/settlements/reminder" style="margin:0">
            <button type="submit" class="btn btn-ghost" style="font-size:12px" onclick="return confirm('ახლავე გაიგზავნოს შეხსენება?')">
                📤 ახლავე გაგზავნა
            </button>
        </form>
    </div>
</div>

<style>
.stl-tab-bar{display:flex;gap:6px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:0}
.stl-tab{padding:9px 18px;font-size:13.5px;font-weight:700;color:var(--muted);cursor:pointer;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-1px;transition:all .15s}
.stl-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.stl-tab:hover{color:var(--text)}
.stl-badge{display:inline-block;font-size:10px;font-weight:800;padding:1px 6px;border-radius:999px;background:var(--accent);color:#fff;margin-left:4px;vertical-align:middle}
.stat-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.stat-box{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center}
.stat-box .val{font-size:22px;font-weight:900;margin-bottom:3px}
.stat-box .lbl{font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.4px}
.sel-check{width:16px;height:16px;cursor:pointer}
.bank-tag{font-size:11px;font-family:monospace;background:var(--surface2,#f1f5f9);padding:2px 7px;border-radius:4px;color:var(--muted)}
.status-pill{display:inline-block;font-size:10.5px;font-weight:700;padding:2px 9px;border-radius:999px}
</style>

<!-- Period bar -->
<form method="GET" action="<?= e($base)?>/manage/taxi/settlements" style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:20px">
    <input type="hidden" name="tab" value="<?= e($tab)?>">
    <div>
        <label class="form-label" style="font-size:11px;margin-bottom:4px">From</label>
        <input type="date" name="from" class="form-input" value="<?= e($dateFrom)?>" style="width:150px">
    </div>
    <div>
        <label class="form-label" style="font-size:11px;margin-bottom:4px">To</label>
        <input type="date" name="to"   class="form-input" value="<?= e($dateTo)?>"   style="width:150px">
    </div>
    <button type="submit" class="btn btn-primary" style="font-size:13px">🔍 Calculate</button>
</form>

<!-- Tabs -->
<div class="stl-tab-bar">
    <a href="?tab=calculate&from=<?= e($dateFrom)?>&to=<?= e($dateTo)?>" class="stl-tab <?= $tab==='calculate'?'active':''?>">
        📊 გაანგარიშება<?= count($calcData)>0?'<span class="stl-badge">'.count($calcData).'</span>':'' ?>
    </a>
    <a href="?tab=history&from=<?= e($dateFrom)?>&to=<?= e($dateTo)?>" class="stl-tab <?= $tab==='history'?'active':''?>">
        📋 ისტორია<?= $history['total']>0?'<span class="stl-badge">'.$history['total'].'</span>':'' ?>
    </a>
</div>

<?php if($tab==='calculate'): ?>

<!-- Summary strip -->
<?php if(!empty($calcData)): ?>
<div class="stat-strip">
    <div class="stat-box"><div class="val" style="color:var(--accent)"><?= count($calcData)?></div><div class="lbl">მძღოლი</div></div>
    <div class="stat-box"><div class="val"><?= number_format($totalGross,2).$sym?></div><div class="lbl">სრული შემოსავალი</div></div>
    <div class="stat-box"><div class="val" style="color:#10b981"><?= number_format(abs($totalDebt),2).$sym?></div><div class="lbl">შემოსავალი (მძღოლი → საიტი)</div></div>
    <div class="stat-box"><div class="val" style="color:#ef4444"><?= number_format($totalPayout,2).$sym?></div><div class="lbl">გასავალი (საიტი → მძღოლი)</div></div>
</div>
<?php endif ?>

<form method="POST" action="<?= e($base)?>/manage/taxi/settlements/create" id="settleForm">
    <input type="hidden" name="date_from" value="<?= e($dateFrom)?>">
    <input type="hidden" name="date_to"   value="<?= e($dateTo)?>">

<?php if(empty($calcData)): ?>
<div class="card"><div style="padding:40px;text-align:center;color:var(--muted)">
    <div style="font-size:40px;margin-bottom:12px">📭</div>
    <div>ამ პერიოდში დასრულებული და გადახდილი მგზავრობები არ მოიძებნა.</div>
</div></div>
<?php else: ?>
<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <div style="display:flex;align-items:center;gap:10px">
            <h3>მძღოლების გადახდები</h3>
            <span style="font-size:12px;color:var(--muted)"><?= e($dateFrom)?> – <?= e($dateTo)?></span>
            <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:#f0fdf4;color:#10b981">ქეში + ბარათი</span>
        </div>
        <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-ghost" style="font-size:12px" onclick="toggleAll(true)">ყველა ✓</button>
            <button type="button" class="btn btn-ghost" style="font-size:12px" onclick="toggleAll(false)">გასუფთავება</button>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:36px"><input type="checkbox" class="sel-check" onchange="toggleAll(this.checked)"></th>
                <th>მძღოლი</th>
                <th>ბანკი / IBAN</th>
                <th style="text-align:right">მგზავრ.</th>
                <th style="text-align:right">სრული ქეში</th>
                <th style="text-align:right">კომისია</th>
                <th style="text-align:right">ანგარიშსწ.</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($calcData as $row):
            $d = $row['driver'];
            $hasBank = !empty($d['bank_account']);
            $isDebt  = $row['net_amount'] < 0;
        ?>
        <tr style="background:<?= $isDebt ? 'rgba(16,185,129,.04)' : 'rgba(239,68,68,.04)'?>">
            <td><input type="checkbox" name="driver_ids[]" value="<?= (int)$d['id']?>" class="sel-check driver-check" <?= (!$isDebt&&$hasBank)?'checked':''?>></td>
            <td>
                <div style="font-weight:700;font-size:13.5px"><?= e($d['name'])?></div>
                <div style="font-size:11.5px;color:var(--muted)"><?= e($d['phone']??'')?></div>
            </td>
            <td>
                <?php if($hasBank): ?>
                <span class="bank-tag"><?= e($d['bank_account'])?></span><br>
                <span style="font-size:11px;color:var(--muted)"><?= e($d['bank_name']??'')?></span>
                <?php else: ?>
                <span style="font-size:11.5px;color:#ef4444;font-weight:700">⚠ IBAN არ არის</span>
                <a href="<?= e($base)?>/manage/taxi/drivers" style="font-size:11px;color:var(--accent);margin-left:6px">დამატება →</a>
                <?php endif ?>
            </td>
            <td style="text-align:right;font-weight:700"><?= $row['rides_count']?></td>
            <td style="text-align:right"><?= number_format($row['gross_amount'],2).$sym?></td>
            <td style="text-align:right;color:#ef4444">−<?= number_format($row['commission'],2).$sym?></td>
            <td style="text-align:right">
                <?php if($isDebt): ?>
                <span style="font-size:15px;font-weight:900;color:#10b981"><?= number_format(abs($row['net_amount']),2).$sym?></span>
                <?php else: ?>
                <span style="font-size:15px;font-weight:900;color:#ef4444"><?= number_format($row['net_amount'],2).$sym?></span>
                <?php endif ?>
            </td>
            <td></td>
        </tr>
        <?php endforeach ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:900;background:var(--surface2,#f8fafc)">
                <td colspan="3" style="padding:10px 16px">სულ</td>
                <td style="text-align:right;padding:10px 8px"><?= array_sum(array_column($calcData,'rides_count'))?></td>
                <td style="text-align:right;padding:10px 8px"><?= number_format($totalGross,2).$sym?></td>
                <td style="text-align:right;padding:10px 8px;color:#ef4444">−<?= number_format($totalComm,2).$sym?></td>
                <td style="text-align:right;padding:10px 8px;font-size:13px;font-weight:900;line-height:1.7">
                    <?php if($totalDebt < 0): ?>
                    <span style="color:#10b981">↓ შემოსავალი <?= number_format(abs($totalDebt),2).$sym?></span><br>
                    <?php endif ?>
                    <?php if($totalPayout > 0): ?>
                    <span style="color:#ef4444">↑ გასავალი <?= number_format($totalPayout,2).$sym?></span>
                    <?php endif ?>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border)">
        <span style="font-size:13px;color:var(--muted)" id="selCount">ყველა მონიშნული</span>
        <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 28px">
            💳 ანგარიშსწორების ჩანაწერების შექმნა
        </button>
    </div>
</div>
<?php endif ?>
</form>

<?php elseif($tab==='history'): ?>

<!-- Settlement history -->
<?php if(empty($history['items'])): ?>
<div class="card"><div style="padding:40px;text-align:center;color:var(--muted)">
    <div style="font-size:40px;margin-bottom:12px">📋</div>
    <div>ანგარიშსწორების ისტორია ცარიელია.</div>
</div></div>
<?php else: ?>
<div class="card">
    <div class="card-header"><h3>ანგარიშსწორების ჩანაწერები</h3></div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th><th>მძღოლი</th><th>პერიოდი</th><th>IBAN</th>
                <th style="text-align:right">მგზავ.</th>
                <th style="text-align:right">სრული</th>
                <th style="text-align:right">გასარიცხი</th>
                <th>სტატუსი</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($history['items'] as $s):
            $d = $s['driver'] ?? [];
            $statusColors = ['pending'=>['#f59e0b','#fffbeb'],'processing'=>['#3b82f6','#eff6ff'],'paid'=>['#10b981','#f0fdf4'],'failed'=>['#ef4444','#fef2f2']];
            $statusLabels = ['pending'=>'მოლოდინი','processing'=>'მიმდინარე','paid'=>'✓ გადახდილი','failed'=>'✕ შეცდომა'];
            [$sc,$sb] = $statusColors[$s['status']] ?? ['#64748b','#f1f5f9'];
            $sl = $statusLabels[$s['status']] ?? $s['status'];
        ?>
        <tr>
            <td style="font-family:monospace;font-size:12px;color:var(--muted)">#<?= (int)$s['id']?></td>
            <td>
                <div style="font-weight:700"><?= e($d['name']??'—')?></div>
                <div style="font-size:11px;color:var(--muted)"><?= e($d['phone']??'')?></div>
            </td>
            <td style="font-size:12px;color:var(--muted)">
                <?= $s['period_from']?date('d.m.Y',strtotime($s['period_from'])):'' ?>
                <?= $s['period_to']?' – '.date('d.m.Y',strtotime($s['period_to'])):'' ?>
            </td>
            <td><span class="bank-tag"><?= e($s['bank_account']?:'—')?></span></td>
            <td style="text-align:right"><?= (int)$s['rides_count']?></td>
            <td style="text-align:right"><?= number_format((float)$s['gross_amount'],2).$sym?></td>
            <td style="text-align:right;font-weight:900;color:#10b981;font-size:15px"><?= number_format((float)$s['net_amount'],2).$sym?></td>
            <td><span class="status-pill" style="background:<?=$sb?>;color:<?=$sc?>"><?= $sl?></span>
                <?php if($s['bank_ref']): ?><div style="font-size:10px;color:var(--muted);font-family:monospace"><?= e($s['bank_ref'])?></div><?php endif ?>
            </td>
            <td>
                <?php if($s['status']==='pending'||$s['status']==='processing'): ?>
                <button type="button" class="btn btn-ghost" style="font-size:12px"
                    onclick="showPayModal(<?= (int)$s['id']?>,<?= json_encode(number_format((float)$s['net_amount'],2).$sym)?>,<?= json_encode($d['name']??'') ?>)">
                    ✓ მონიშნე გადახდილად
                </button>
                <?php elseif($s['status']==='paid'&&$s['paid_at']): ?>
                <span style="font-size:11px;color:var(--muted)"><?= date('d.m.Y',strtotime($s['paid_at']))?></span>
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>
<?php endif ?>

<!-- Mark Paid Modal -->
<div id="payModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:var(--surface);border-radius:16px;padding:28px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="font-size:16px;font-weight:800;margin-bottom:4px">✓ გადახდის დადასტურება</div>
        <div style="font-size:13px;color:var(--muted);margin-bottom:18px" id="payModalDesc"></div>
        <form method="POST" id="payForm">
            <input type="hidden" name="action" value="paid">
            <div class="form-group">
                <label class="form-label">ბანკის საიდენტიფიკაციო კოდი (სურვილისამებრ)</label>
                <input type="text" name="bank_ref" class="form-input" placeholder="TXN-12345...">
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="btn btn-primary" style="flex:1">✓ დადასტურება</button>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('payModal').style.display='none'">გაუქმება</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAll(v){
    document.querySelectorAll('.driver-check').forEach(function(c){c.checked=!!v;});
    updateCount();
}
function updateCount(){
    var n=document.querySelectorAll('.driver-check:checked').length;
    var el=document.getElementById('selCount');
    if(el) el.textContent=n+' მძღოლი მონიშნული';
}
document.querySelectorAll('.driver-check').forEach(function(c){c.addEventListener('change',updateCount);});
updateCount();

function showPayModal(id,amount,name){
    document.getElementById('payModalDesc').textContent = name+' · '+amount;
    document.getElementById('payForm').action = <?= json_encode($base.'/manage/taxi/settlements/')?> + id + '/paid';
    document.getElementById('payModal').style.display='flex';
}
document.getElementById('payModal').addEventListener('click',function(e){if(e.target===this)this.style.display='none';});
</script>
