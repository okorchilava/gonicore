<?php
/**
 * GoniStore — Checkout page
 * Variables: $cart, $totals, $coupon, $error, $settings, $base
 */
$symbol = $settings['currency_symbol'] ?? '$';
?>
<style>
.gs-checkout{max-width:1100px;margin:0 auto;padding:40px 24px}
.gs-checkout h1{font-size:26px;font-weight:800;margin-bottom:28px;color:#0f172a}
.gs-checkout-layout{display:grid;grid-template-columns:1fr 360px;gap:36px;align-items:start}
.gs-section{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px}
.gs-section h2{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #f1f5f9}
.gs-field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
.gs-field{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.gs-field.half{margin-bottom:0}
.gs-field label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b}
.gs-field label .req{color:#ef4444}
.gs-field input,.gs-field select,.gs-field textarea{padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;color:#0f172a;background:#fff;transition:border-color .15s;width:100%}
.gs-field input:focus,.gs-field select:focus,.gs-field textarea:focus{outline:none;border-color:#10B27C}
.gs-field textarea{resize:vertical;min-height:80px}
/* Payment */
.gs-payment-method{display:flex;flex-direction:column;gap:10px}
.gs-payment-option{display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:9px;cursor:pointer;transition:border-color .15s}
.gs-payment-option:has(input:checked){border-color:#10B27C;background:#f0fdf9}
.gs-payment-option input[type=radio]{accent-color:#10B27C;width:16px;height:16px}
.gs-payment-option .pm-label{font-size:14px;font-weight:600;color:#0f172a}
.gs-payment-option .pm-desc{font-size:12px;color:#64748b}
/* Order sidebar */
.gs-order-summary{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;position:sticky;top:24px}
.gs-order-summary h2{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9}
.gs-order-item{display:flex;justify-content:space-between;align-items:start;font-size:13px;margin-bottom:10px;gap:8px}
.gs-order-item-name{color:#374151;flex:1}
.gs-order-item-name small{display:block;color:#94a3b8;font-size:11px}
.gs-order-item-price{font-weight:700;color:#0f172a;white-space:nowrap}
.gs-divider{border:none;border-top:1px solid #f1f5f9;margin:12px 0}
.gs-summary-row{display:flex;justify-content:space-between;font-size:13px;color:#374151;margin-bottom:8px}
.gs-summary-row.total{font-size:16px;font-weight:700;color:#0f172a;padding-top:8px;border-top:1px solid #e2e8f0;margin-top:4px}
.gs-summary-row .green{color:#10B27C}
.gs-summary-row .discount{color:#ef4444}
.gs-place-btn{display:block;width:100%;padding:14px;background:#10B27C;color:#fff;border:none;border-radius:9px;font-size:16px;font-weight:700;cursor:pointer;transition:background .15s;margin-top:18px;text-align:center}
.gs-place-btn:hover{background:#0e9c6c}
.gs-error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px}
@media(max-width:768px){.gs-checkout-layout{grid-template-columns:1fr}.gs-order-summary{position:static}.gs-field-row{grid-template-columns:1fr}}
</style>

<div class="gs-checkout">
    <h1>Checkout</h1>

    <?php if (!empty($error)): ?>
    <div class="gs-error-msg">⚠ <?= e($error) ?></div>
    <?php endif ?>

    <form method="POST" action="<?= e($base) ?>/checkout/place" id="gs-checkout-form">
        <div class="gs-checkout-layout">
            <!-- Left: Billing + Payment -->
            <div>
                <!-- Billing Details -->
                <div class="gs-section">
                    <h2>Billing Details</h2>
                    <div class="gs-field-row">
                        <div class="gs-field half">
                            <label>First Name <span class="req">*</span></label>
                            <input type="text" name="billing_first_name" required placeholder="John">
                        </div>
                        <div class="gs-field half">
                            <label>Last Name</label>
                            <input type="text" name="billing_last_name" placeholder="Doe">
                        </div>
                    </div>
                    <div class="gs-field-row">
                        <div class="gs-field half">
                            <label>Email <span class="req">*</span></label>
                            <input type="email" name="billing_email" required placeholder="john@example.com">
                        </div>
                        <div class="gs-field half">
                            <label>Phone</label>
                            <input type="tel" name="billing_phone" placeholder="+1 555 000 0000">
                        </div>
                    </div>
                    <div class="gs-field">
                        <label>Address</label>
                        <input type="text" name="billing_address" placeholder="123 Main Street, Apt 4B">
                    </div>
                    <div class="gs-field-row">
                        <div class="gs-field half">
                            <label>City</label>
                            <input type="text" name="billing_city" placeholder="New York">
                        </div>
                        <div class="gs-field half">
                            <label>State / Province</label>
                            <input type="text" name="billing_state" placeholder="NY">
                        </div>
                    </div>
                    <div class="gs-field-row">
                        <div class="gs-field half">
                            <label>ZIP / Postal Code</label>
                            <input type="text" name="billing_zip" placeholder="10001">
                        </div>
                        <div class="gs-field half">
                            <label>Country</label>
                            <input type="text" name="billing_country" placeholder="United States">
                        </div>
                    </div>
                    <div class="gs-field">
                        <label>Order Notes (optional)</label>
                        <textarea name="customer_note" placeholder="Any special notes about your order…"></textarea>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="gs-section">
                    <h2>Payment Method</h2>
                    <div class="gs-payment-method">
                        <label class="gs-payment-option">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div>
                                <div class="pm-label">💵 Cash on Delivery</div>
                                <div class="pm-desc">Pay when you receive your order.</div>
                            </div>
                        </label>
                        <label class="gs-payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer">
                            <div>
                                <div class="pm-label">🏦 Bank Transfer</div>
                                <div class="pm-desc">Make a direct bank transfer. We'll send details after your order.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right: Order Summary -->
            <div class="gs-order-summary">
                <h2>Your Order</h2>

                <?php foreach ($cart as $item):
                    $attrs = $item['attrs'] ?? [];
                    $attrStr = implode(', ', array_map(fn($k,$v)=>$k.': '.$v, array_keys($attrs), $attrs));
                ?>
                <div class="gs-order-item">
                    <div class="gs-order-item-name">
                        <?= e($item['name']) ?> &times;<?= (int)$item['qty'] ?>
                        <?php if ($attrStr): ?><small><?= e($attrStr) ?></small><?php endif ?>
                    </div>
                    <div class="gs-order-item-price"><?= $symbol ?><?= number_format((float)$item['price'] * (int)$item['qty'],2) ?></div>
                </div>
                <?php endforeach ?>

                <hr class="gs-divider">

                <div class="gs-summary-row">
                    <span>Subtotal</span>
                    <span><?= $symbol ?><?= number_format((float)($totals['subtotal']??0),2) ?></span>
                </div>
                <?php if (!empty($totals['tax'])): ?>
                <div class="gs-summary-row">
                    <span>Tax</span>
                    <span><?= $symbol ?><?= number_format((float)$totals['tax'],2) ?></span>
                </div>
                <?php endif ?>
                <?php if (!empty($totals['shipping'])): ?>
                <div class="gs-summary-row">
                    <span>Shipping</span>
                    <span><?= (float)$totals['shipping']>0 ? $symbol.number_format((float)$totals['shipping'],2) : '<span class="green">Free</span>' ?></span>
                </div>
                <?php endif ?>
                <?php if (!empty($coupon)): ?>
                <div class="gs-summary-row">
                    <span>Coupon (<?= e($coupon['code']) ?>)</span>
                    <span class="discount">−<?= $symbol ?><?= number_format((float)$coupon['discount'],2) ?></span>
                </div>
                <?php endif ?>
                <div class="gs-summary-row total">
                    <span>Total</span>
                    <span class="green"><?= $symbol ?><?= number_format((float)($totals['total']??0) - (float)($coupon['discount']??0),2) ?></span>
                </div>

                <button type="submit" class="gs-place-btn">Place Order →</button>
                <p style="font-size:12px;color:#94a3b8;text-align:center;margin-top:10px">
                    By placing your order you agree to our terms.
                </p>
            </div>
        </div>
    </form>
</div>
