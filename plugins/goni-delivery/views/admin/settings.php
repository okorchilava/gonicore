<?php
$pageTitle = 'Delivery Settings';
$activeNav = 'delivery-settings';
$topbarActions = '';
?>
<?php if (!empty($saved)): ?><div id="gc-flash" data-msg="Settings saved." data-icon="success" style="display:none"></div><?php endif ?>
<div style="max-width:600px">
<form method="POST" action="<?= e($base) ?>/manage/delivery/settings" style="display:flex;flex-direction:column;gap:16px">

    <div class="card">
        <div class="card-header"><h3>General</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <div class="form-group" style="margin:0"><label class="form-label">Brand Name</label>
                <input type="text" name="brand_name" class="form-input" value="<?= e($delivery->setting('brand_name','GoniDelivery')) ?>"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Support Phone</label>
                <input type="text" name="phone" class="form-input" value="<?= e($delivery->setting('phone','')) ?>" placeholder="+995 555 000 000"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Page Slug</label>
                <input type="text" name="page_slug" class="form-input" value="<?= e($delivery->setting('page_slug','delivery')) ?>"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Pricing</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0"><label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-input" value="<?= e($delivery->setting('currency','GEL')) ?>" maxlength="5"></div>
                <div class="form-group" style="margin:0"><label class="form-label">Symbol</label>
                    <input type="text" name="currency_symbol" class="form-input" value="<?= e($delivery->setting('currency_symbol','₾')) ?>" maxlength="5"></div>
                <div class="form-group" style="margin:0"><label class="form-label">Base Fee</label>
                    <input type="number" name="base_fee" class="form-input" value="<?= e($delivery->setting('base_fee','3')) ?>" min="0" step="0.01"></div>
            </div>
            <hr style="border:none;border-top:1px solid #e2e8f0">
            <div>
                <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">Below Minimum Order Surcharge</div>
                <div class="form-group" style="margin:0;max-width:200px"><label class="form-label">Extra Fee (when order &lt; min_order)</label>
                    <input type="number" name="below_min_surcharge" class="form-input" value="<?= e($delivery->setting('below_min_surcharge','0')) ?>" min="0" step="0.01" placeholder="0.00"></div>
            </div>
            <hr style="border:none;border-top:1px solid #e2e8f0">
            <div>
                <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">Distance Surcharge 📏</div>
                <div style="font-size:12px;color:#94a3b8;margin-bottom:10px">თუ კურიერის გზა (ობიექტი → მომხმარებელი) გადააჭარბებს ზღვარს, ემატება საფასური თითო კილომეტრზე.</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:420px">
                    <div class="form-group" style="margin:0"><label class="form-label">ზღვარი (კმ)</label>
                        <input type="number" name="per_km_threshold" class="form-input" value="<?= e($delivery->setting('per_km_threshold','5')) ?>" min="0" step="0.1" placeholder="5"></div>
                    <div class="form-group" style="margin:0"><label class="form-label">ფასი კმ-ზე</label>
                        <input type="number" name="per_km_rate" class="form-input" value="<?= e($delivery->setting('per_km_rate','0')) ?>" min="0" step="0.01" placeholder="0.00"></div>
                </div>
            </div>
            <hr style="border:none;border-top:1px solid #e2e8f0">
            <div>
                <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">Weather Surcharge ☔</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:420px">
                    <div class="form-group" style="margin:0"><label class="form-label">Amount</label>
                        <input type="number" name="weather_surcharge_amount" class="form-input" value="<?= e($delivery->setting('weather_surcharge_amount','0')) ?>" min="0" step="0.01" placeholder="0.00"></div>
                    <div class="form-group" style="margin:0;display:flex;align-items:flex-end;padding-bottom:4px">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer">
                            <input type="hidden" name="weather_surcharge_active" value="0">
                            <input type="checkbox" name="weather_surcharge_active" value="1" <?= $delivery->setting('weather_surcharge_active','0')==='1'?'checked':'' ?>> Active now
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Services</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer">
                <input type="hidden" name="courier_enabled" value="0">
                <input type="checkbox" name="courier_enabled" value="1" <?= $delivery->setting('courier_enabled','1')==='1'?'checked':'' ?>> Enable Courier Delivery
            </label>
            <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer">
                <input type="hidden" name="food_enabled" value="0">
                <input type="checkbox" name="food_enabled" value="1" <?= $delivery->setting('food_enabled','1')==='1'?'checked':'' ?>> Enable Food Delivery
            </label>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 28px;align-self:start">Save Settings</button>
</form>
</div>
