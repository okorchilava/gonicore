<?php $pageTitle='Taxi Settings'; $activeNav='taxi-settings'; $topbarActions=''; ?>
<?php if(!empty($saved)): ?><div id="gc-flash" data-msg="Settings saved." data-icon="success" style="display:none"></div><?php endif ?>
<div style="max-width:600px">
<form method="POST" action="<?= e($base)?>/manage/taxi/settings" style="display:flex;flex-direction:column;gap:16px">
    <div class="card"><div class="card-header"><h3>General</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <div class="form-group" style="margin:0"><label class="form-label">Brand Name</label><input type="text" name="brand_name" class="form-input" value="<?= e($taxi->setting('brand_name','GoniTaxi'))?>"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Support Phone</label><input type="text" name="phone" class="form-input" value="<?= e($taxi->setting('phone',''))?>" placeholder="+995 555 000 000"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Page Slug</label><input type="text" name="page_slug" class="form-input" value="<?= e($taxi->setting('page_slug','taxi'))?>"></div>
            <div class="form-group" style="margin:0">
                <label class="form-label">🔔 Admin Email <span style="font-size:11px;color:var(--muted);font-weight:400">(ანგარიშსწორების შეხსენებები)</span></label>
                <input type="email" name="admin_email" class="form-input" value="<?= e($taxi->setting('admin_email',''))?>" placeholder="admin@example.com">
            </div>
        </div>
    </div>
    <div class="card"><div class="card-header"><h3>Fare Calculator</h3></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
            <div class="form-group" style="margin:0"><label class="form-label">Currency</label><input type="text" name="currency" class="form-input" value="<?= e($taxi->setting('currency','GEL'))?>" maxlength="5"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Symbol</label><input type="text" name="currency_symbol" class="form-input" value="<?= e($taxi->setting('currency_symbol','₾'))?>" maxlength="5"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Base Fare</label><input type="number" name="base_fare" class="form-input" value="<?= e($taxi->setting('base_fare','5'))?>" min="0" step="0.01"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Price per km</label><input type="number" name="price_per_km" class="form-input" value="<?= e($taxi->setting('price_per_km','1.5'))?>" min="0" step="0.01"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Min Fare</label><input type="number" name="min_fare" class="form-input" value="<?= e($taxi->setting('min_fare','5'))?>" min="0" step="0.01"></div>
        </div>
        <div style="padding:0 20px 16px;font-size:12.5px;color:var(--muted)">Fare = max(Min Fare, Base + km × Price per km) × car type multiplier (Economy ×0.8 / Sedan ×1.0 / SUV ×1.3 / Minivan ×1.4)</div>
    </div>
    <div class="card"><div class="card-header"><h3>⏱ მოლოდინის ტარიფი</h3></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group" style="margin:0">
                <label class="form-label">უფასო მოლოდინი (წუთი)</label>
                <input type="number" name="waiting_free_minutes" class="form-input" value="<?= e($taxi->setting('waiting_free_minutes','3'))?>" min="0" step="1" placeholder="3">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">ტარიფი / წუთი</label>
                <input type="number" name="waiting_rate_per_min" class="form-input" value="<?= e($taxi->setting('waiting_rate_per_min','0.3'))?>" min="0" step="0.01" placeholder="0.30">
            </div>
        </div>
        <div style="padding:0 20px 14px;font-size:12.5px;color:var(--muted)">
            პირველი <?= e($taxi->setting('waiting_free_minutes','3')) ?> წუთი უფასო, შემდეგ <?= e($taxi->setting('waiting_rate_per_min','0.3')) ?> <?= e($taxi->setting('currency_symbol','₾')) ?>/წუთი
        </div>
    </div>
    <div class="card"><div class="card-header"><h3>Commission</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Platform Commission %</label>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="number" name="commission_pct" class="form-input" style="width:120px"
                           value="<?= e($taxi->setting('commission_pct','20'))?>" min="0" max="100" step="0.1">
                    <span style="font-size:13px;color:var(--muted)">Driver receives <?= round(100-(float)$taxi->setting('commission_pct','20'),1) ?>% of each fare</span>
                </div>
            </div>
            <?php
            $commPct = (float)$taxi->setting('commission_pct','20');
            $eg = 50.00;
            $driverEg = round($eg*(1-$commPct/100),2);
            $platEg   = round($eg-$driverEg,2);
            ?>
            <div style="background:var(--surface);border-radius:8px;padding:12px 16px;font-size:13px">
                Example — ₾<?= number_format($eg,2)?> fare:
                <span style="color:#4f46e5;font-weight:700">Platform ₾<?= number_format($platEg,2)?></span> ·
                <span style="color:#10b981;font-weight:700">Driver ₾<?= number_format($driverEg,2)?></span>
            </div>
        </div>
    </div>
    <div class="card"><div class="card-header"><h3>🏢 კომპანიის რეკვიზიტები (XML გადარიცხვისთვის)</h3></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group" style="margin:0">
                <label class="form-label">კომპანიის სახელი</label>
                <input type="text" name="company_name" class="form-input" value="<?= e($taxi->setting('company_name',''))?>" placeholder="შპს GoniTaxi">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">საიდენტიფიკაციო კოდი (INN)</label>
                <input type="text" name="company_inn" class="form-input" value="<?= e($taxi->setting('company_inn',''))?>" placeholder="205XXXXXXX">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">გამგზავნის ანგარიში (IBAN)</label>
                <input type="text" name="company_bank_account" class="form-input" style="font-family:monospace"
                       value="<?= e($taxi->setting('company_bank_account',''))?>" placeholder="GE00BG0000000000000000">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">ბანკის კოდი</label>
                <input type="text" name="company_bank_code" class="form-input" value="<?= e($taxi->setting('company_bank_code',''))?>" placeholder="BAGAGE22">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">საგადასახადო სარეგისტრაციო კოდი</label>
                <input type="text" name="company_tax_reg_code" class="form-input" value="<?= e($taxi->setting('company_tax_reg_code',''))?>" placeholder="...">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">DispatchType (გადარიცხვის მეთოდი)</label>
                <input type="text" name="dispatch_type" class="form-input" value="<?= e($taxi->setting('dispatch_type','0'))?>" placeholder="0">
            </div>
        </div>
        <div style="padding:0 20px 14px;font-size:12px;color:var(--muted)">ეს მონაცემები გამოიყენება BOG CIB XML ფაილის გენერირებისთვის ანგარიშსწორებისას.</div>
    </div>
    <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 28px;align-self:start">Save Settings</button>
</form>
</div>
