<?php $pageTitle='Taxi Drivers'; $activeNav='taxi-drivers'; $topbarActions=''; ?>
<?php if(!empty($saved)): ?><div id="gc-flash" data-msg="Saved." data-icon="success" style="display:none"></div><?php endif ?>
<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">
<div class="card">
    <div class="card-header"><h3>Drivers (<?= count($drivers)?>)</h3></div>
    <?php if(empty($drivers)): ?><div class="empty"><div class="empty-icon">🧑‍✈️</div><h3>No drivers yet</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr><th>Name</th><th>Phone</th><th>Vehicle</th><th>Type</th><th>Status</th><th>Balance</th><th>IBAN</th><th>Login</th><th>Portal</th><th></th></tr></thead><tbody>
    <?php foreach($drivers as $d): ?>
    <tr>
        <td><strong><?= e($d['name'])?></strong></td>
        <td><?= e($d['phone'])?></td>
        <td style="font-size:13px">
            <?= e($d['car_model']) ?><?= $d['car_number']?' · '.e($d['car_number']):'' ?>
            <?php if(!empty($d['car_color'])): ?>
            <span style="display:inline-flex;align-items:center;gap:4px;margin-left:4px;font-size:12px;color:var(--muted)">
                <span style="width:10px;height:10px;border-radius:50%;background:<?= e($d['car_color']) ?>;border:1px solid rgba(0,0,0,.15);flex-shrink:0;display:inline-block"></span>
                <?= e(ucfirst($d['car_color'])) ?>
            </span>
            <?php endif ?>
        </td>
        <td style="font-size:13px"><?= e($taxi->carTypes()[$d['car_type']]??$d['car_type'])?></td>
        <td><span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?=$d['status']==='active'?'#d1fae5':($d['status']==='busy'?'#fef3c7':'#fef2f2')?>;color:<?=$d['status']==='active'?'#059669':($d['status']==='busy'?'#92400e':'#b91c1c')?>"><?= ucfirst($d['status'])?></span></td>
        <td style="font-weight:700;color:#10b981"><?= $taxi->setting('currency_symbol','₾') ?><?= number_format((float)($d['balance']??0),2) ?></td>
        <td style="font-size:11.5px;font-family:monospace;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($d['bank_account']??'')?>">
            <?php if(!empty($d['bank_account'])): ?>
            <span style="color:#10b981">✓</span> <?= e($d['bank_account'])?>
            <?php else: ?><span style="color:#ef4444;font-size:11px;font-family:inherit">⚠ არ არის</span><?php endif ?>
        </td>
        <td style="text-align:center">
            <?php if(!empty($d['password_hash'])): ?>
                <span title="Password set — driver can login" style="color:#10b981;font-size:16px">🔒</span>
            <?php else: ?>
                <span title="No password — driver cannot login" style="color:#ef4444;font-size:16px" onclick="editDriver(<?= htmlspecialchars(json_encode($d),ENT_QUOTES)?>);document.getElementById('dPassword').focus()" style="cursor:pointer">⚠️</span>
            <?php endif ?>
        </td>
        <td style="font-size:12px">
            <?php $tok = !empty($d['driver_token']) ? $d['driver_token'] : $taxi->ensureDriverToken((int)$d['id']); ?>
            <?php if ($tok): ?>
            <a href="<?= e($base) ?>/taxi/driver/<?= e($tok) ?>" target="_blank" title="Driver portal link">🔗 Portal</a>
            <form method="POST" action="<?= e($base) ?>/manage/taxi/drivers/<?= (int)$d['id'] ?>/token" style="display:inline;margin-left:4px">
                <button type="submit" class="btn" style="font-size:10px;padding:2px 6px" title="Generate new token">↻</button>
            </form>
            <?php else: ?>—<?php endif ?>
        </td>
        <td>
            <button type="button" class="btn btn-ghost" style="font-size:12px" onclick="editDriver(<?= htmlspecialchars(json_encode($d),ENT_QUOTES)?>)">Edit</button>
            <form method="POST" action="<?= e($base)?>/manage/taxi/drivers/<?=(int)$d['id']?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px" onclick="gcConfirm(this,'Delete driver?','','Delete')">✕</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?></tbody></table>
    <?php endif ?>
</div>
<div class="card">
    <div class="card-header"><h3 id="dTitle">Add Driver</h3></div>
    <div class="card-body">
        <form method="POST" action="<?= e($base)?>/manage/taxi/drivers/create" id="dForm" style="display:flex;flex-direction:column;gap:9px">
            <input type="text"  name="name"       class="form-input" placeholder="Full Name *" required style="padding:8px 12px;font-size:13px">
            <input type="tel"   name="phone"       class="form-input" placeholder="Phone *"     required style="padding:8px 12px;font-size:13px">
            <input type="email" name="email"       class="form-input" placeholder="Email"               style="padding:8px 12px;font-size:13px">
            <input type="text"  name="car_model"   class="form-input" placeholder="Car model (Toyota Camry…)" style="padding:8px 12px;font-size:13px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <input type="text" name="car_number" class="form-input" placeholder="Plate number" style="padding:8px 12px;font-size:13px">
                <div style="position:relative;display:flex;align-items:center;gap:6px">
                    <input type="color" name="car_color_picker" id="dColorPicker"
                           style="width:36px;height:36px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px;flex-shrink:0"
                           oninput="syncColor(this.value)">
                    <input type="text" name="car_color" id="dColorText" class="form-input"
                           placeholder="Color name e.g. Black"
                           style="padding:8px 12px;font-size:13px;flex:1">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <select name="car_type" class="form-select" style="padding:8px 12px;font-size:13px">
                    <?php foreach($taxi->carTypes() as $v=>$l): ?><option value="<?=$v?>"><?= e($l)?></option><?php endforeach ?>
                </select>
                <select name="status" class="form-select" style="padding:8px 12px;font-size:13px">
                    <option value="active">Active</option><option value="busy">Busy</option><option value="inactive">Inactive</option>
                </select>
            </div>
            <div style="border-top:1px solid var(--border);padding-top:10px;margin-top:4px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px">
                    <span style="font-size:14px">🏦</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text)">საბანკო რეკვიზიტები (ანგარიშსწორებისთვის)</span>
                </div>
                <input type="text" name="bank_account" id="dBankAccount" class="form-input"
                       placeholder="IBAN (GE00BG0000000000000000)"
                       style="padding:8px 12px;font-size:13px;margin-bottom:6px;font-family:monospace">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:10px">
                    <input type="text" name="bank_name" id="dBankName" class="form-input"
                           placeholder="ბანკის სახელი" style="padding:8px 12px;font-size:13px">
                    <input type="text" name="bank_code" id="dBankCode" class="form-input"
                           placeholder="ბანკის კოდი (BAGAGE22)" style="padding:8px 12px;font-size:13px">
                </div>
                <input type="text" name="personal_id" id="dPersonalId" class="form-input"
                       placeholder="პირადი ნომერი / INN (01XXXXXXXXXX)"
                       style="padding:8px 12px;font-size:13px;margin-bottom:10px">
            </div>
            <div style="border-top:1px solid var(--border);padding-top:10px;margin-top:4px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px">
                    <span style="font-size:14px">🔒</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text)">Portal Login Password</span>
                </div>
                <input type="password" name="password" id="dPassword" class="form-input"
                       placeholder="New password (leave blank to keep current)"
                       style="padding:8px 12px;font-size:13px;margin-bottom:6px">
                <input type="password" name="password_confirm" id="dPasswordConfirm" class="form-input"
                       placeholder="Confirm new password"
                       style="padding:8px 12px;font-size:13px">
                <div style="font-size:11.5px;color:var(--muted);margin-top:6px">
                    Driver uses their phone number + this password to login at <strong>/taxi/driver/login</strong>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="font-size:13px">Save Driver</button>
            <button type="button" class="btn btn-ghost" style="font-size:13px;display:none" id="dCancel" onclick="resetDriverForm()">Cancel</button>
        </form>
    </div>
</div>
</div>
<script>
function editDriver(d){
    var f=document.getElementById('dForm');
    f.action='<?= e($base)?>/manage/taxi/drivers/'+d.id+'/update';
    f.name.value=d.name; f.phone.value=d.phone; f.email.value=d.email||'';
    f.car_model.value=d.car_model||''; f.car_number.value=d.car_number||'';
    f.car_type.value=d.car_type; f.status.value=d.status;
    var col=d.car_color||'';
    document.getElementById('dColorText').value=col;
    // Try to set color picker (only works with hex values)
    try{ if(col) document.getElementById('dColorPicker').value=col; }catch(e){}
    // Clear password fields — leave blank to keep existing password
    document.getElementById('dPassword').value='';
    document.getElementById('dPasswordConfirm').value='';
    var hasPass = d.password_hash && d.password_hash!=='';
    document.getElementById('dPassword').placeholder = hasPass
        ? 'Leave blank to keep current password'
        : '⚠️ No password set — driver cannot login!';
    document.getElementById('dBankAccount').value=d.bank_account||'';
    document.getElementById('dBankName').value=d.bank_name||'';
    document.getElementById('dBankCode').value=d.bank_code||'';
    document.getElementById('dPersonalId').value=d.personal_id||'';
    document.getElementById('dTitle').textContent='Edit Driver: '+d.name;
    document.getElementById('dCancel').style.display='';
    f.scrollIntoView({behavior:'smooth'});
}
function syncColor(hex){
    document.getElementById('dColorText').value=hex;
}
function resetDriverForm(){
    var f=document.getElementById('dForm');
    f.action='<?= e($base)?>/manage/taxi/drivers/create';
    f.reset();document.getElementById('dTitle').textContent='Add Driver';
    document.getElementById('dCancel').style.display='none';
}
</script>
