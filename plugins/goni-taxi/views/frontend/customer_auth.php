<?php
$slug  = $taxi->setting('page_slug','taxi');
$brand = $taxi->setting('brand_name','GoniTaxi');
?>
<style>
.auth-wrap{min-height:calc(100vh - 80px);display:flex;align-items:center;justify-content:center;padding:24px 16px;background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 100%)}
.auth-box{background:#fff;border-radius:24px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.35)}
.auth-head{background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:36px 28px 28px;text-align:center;color:#fff}
.auth-head-icon{font-size:52px;margin-bottom:10px;filter:drop-shadow(0 4px 8px rgba(0,0,0,.2))}
.auth-head-title{font-size:26px;font-weight:900;margin-bottom:4px}
.auth-head-sub{font-size:13px;opacity:.75}
.auth-body{padding:32px 28px}
.auth-field{margin-bottom:16px}
.auth-label{font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;display:block}
.auth-input{width:100%;border:1.5px solid #e2e8f0;border-radius:12px;padding:14px 16px;font-size:15px;font-family:inherit;outline:none;transition:all .15s;background:#fff;box-sizing:border-box}
.auth-input:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.auth-btn{width:100%;padding:15px;background:#4f46e5;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:all .15s;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:8px}
.auth-btn:hover{background:#4338ca;transform:translateY(-1px);box-shadow:0 4px 16px rgba(79,70,229,.35)}
.auth-btn:disabled{background:#a5b4fc;cursor:not-allowed;transform:none;box-shadow:none}
.auth-err{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 14px;color:#b91c1c;font-size:13px;margin-bottom:18px}
.auth-back{background:none;border:none;color:#64748b;font-size:13px;cursor:pointer;font-family:inherit;padding:4px 0;text-decoration:underline;margin-top:12px;display:block;width:100%;text-align:center}
.auth-back:hover{color:#4f46e5}

/* OTP input grid */
.otp-grid{display:flex;gap:10px;justify-content:center;margin:8px 0 4px}
.otp-digit{width:56px;height:64px;border:2px solid #e2e8f0;border-radius:14px;font-size:28px;font-weight:900;text-align:center;font-family:monospace;outline:none;transition:all .15s;background:#fff;color:#0f172a}
.otp-digit:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.otp-digit.filled{border-color:#4f46e5;background:#eef2ff}

/* Dev OTP badge */
.dev-otp{background:#fef9c3;border:2px solid #f59e0b;border-radius:12px;padding:14px 16px;margin-bottom:16px;display:none;text-align:center}
.dev-otp-label{font-size:11px;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.dev-otp-code{font-size:36px;font-weight:900;font-family:monospace;color:#b45309;letter-spacing:10px}
.dev-otp-note{font-size:11px;color:#92400e;margin-top:6px;opacity:.7}

.resend-row{text-align:center;margin-top:14px;font-size:13px;color:#64748b}
.resend-btn{background:none;border:none;color:#4f46e5;font-weight:700;cursor:pointer;font-family:inherit;font-size:13px}
.resend-btn:disabled{color:#94a3b8;cursor:not-allowed}
</style>

<div class="auth-wrap">
  <div class="auth-box">
    <div class="auth-head">
      <div class="auth-head-icon">🚕</div>
      <div class="auth-head-title"><?= e($brand) ?></div>
      <div class="auth-head-sub" id="headSub">
        <?= $step === 'otp' ? 'შეიყვანე კოდი' : 'შედი ან დარეგისტრირდი' ?>
      </div>
    </div>

    <div class="auth-body" id="authBody">

      <?php if($error): ?>
      <div class="auth-err">⚠️ <?= e($error) ?></div>
      <?php endif ?>

      <!-- ══ STEP 1: Phone ══ -->
      <div id="stepPhone" <?= $step === 'otp' ? 'style="display:none"' : '' ?>>
        <div class="auth-field">
          <label class="auth-label">📱 ტელეფონის ნომერი</label>
          <input type="tel" id="phoneInput" class="auth-input"
                 placeholder="+995 555 000 000"
                 value="<?= e($phone) ?>" autofocus
                 oninput="onPhoneInput()"
                 onkeydown="if(event.key==='Enter'){event.preventDefault();sendOtp()}">
        </div>
        <div class="auth-field" id="nameField" style="display:none">
          <label class="auth-label">👤 სახელი (ახალი მომხმარებელი)</label>
          <input type="text" id="nameInput" class="auth-input" placeholder="შენი სახელი">
        </div>
        <button class="auth-btn" id="sendBtn" onclick="sendOtp()" disabled>
          <span id="sendBtnIcon">📤</span>
          <span id="sendBtnTxt">კოდის გაგზავნა</span>
        </button>
        <div style="text-align:center;margin-top:16px;font-size:12px;color:#94a3b8;line-height:1.5">
          SMS კოდი გაიგზავნება ნომერზე · 10 წუთი მოქმედებს
        </div>
      </div>

      <!-- ══ STEP 2: OTP ══ -->
      <div id="stepOtp" <?= $step !== 'otp' ? 'style="display:none"' : '' ?>>
        <div style="text-align:center;margin-bottom:20px;font-size:13px;color:#64748b">
          კოდი გაიგზავნა ნომერზე <strong id="otpPhoneLabel"><?= e($phone) ?></strong>
        </div>

        <div id="devOtpBox" class="dev-otp">
          <div class="dev-otp-label">🧪 TEST MODE · SMS კოდი</div>
          <div class="dev-otp-code" id="devOtpCode">—</div>
          <div class="dev-otp-note">ყუთები ავტომატურად ივსება · პროდაქშენში APP_DEBUG=false</div>
        </div>

        <div class="otp-grid">
          <input class="otp-digit" type="tel" maxlength="1" id="d0" oninput="otpNext(0)" onkeydown="otpKey(event,0)">
          <input class="otp-digit" type="tel" maxlength="1" id="d1" oninput="otpNext(1)" onkeydown="otpKey(event,1)">
          <input class="otp-digit" type="tel" maxlength="1" id="d2" oninput="otpNext(2)" onkeydown="otpKey(event,2)">
          <input class="otp-digit" type="tel" maxlength="1" id="d3" oninput="otpNext(3)" onkeydown="otpKey(event,3)">
        </div>

        <div id="otpErr" style="display:none;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:10px 14px;color:#b91c1c;font-size:13px;margin:12px 0"></div>

        <button class="auth-btn" id="verifyBtn" onclick="verifyOtp()" style="margin-top:14px" disabled>
          <span>✅</span> <span id="verifyTxt">შესვლა</span>
        </button>

        <div class="resend-row">
          კოდი არ მოვიდა?
          <button class="resend-btn" id="resendBtn" onclick="resendOtp()" disabled>
            ხელახლა გაგზავნა (<span id="resendTimer">60</span>წ)
          </button>
        </div>

        <button class="auth-back" onclick="backToPhone()">← სხვა ნომრით შესვლა</button>
      </div>

    </div>
  </div>
</div>

<script>
var BASE = <?= json_encode($base) ?>;
var SLUG = <?= json_encode($slug) ?>;
var currentPhone = <?= json_encode($phone) ?>;
var resendInterval = null;

// ── Step 1: Phone ─────────────────────────────────────────────────────────────
function onPhoneInput() {
    var v = document.getElementById('phoneInput').value.trim();
    document.getElementById('sendBtn').disabled = v.length < 9;
}
onPhoneInput();

function sendOtp() {
    var phone = document.getElementById('phoneInput').value.trim();
    var name  = (document.getElementById('nameInput') || {value:''}).value.trim();
    if (!phone) return;

    var btn = document.getElementById('sendBtn');
    btn.disabled = true;
    document.getElementById('sendBtnIcon').textContent = '⏳';
    document.getElementById('sendBtnTxt').textContent  = 'იგზავნება...';

    fetch(BASE + '/api/taxi/otp/send', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({phone: phone, name: name})
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (!d.ok) { showPhoneErr(d.error||'შეცდომა'); btn.disabled=false; return; }
        currentPhone = phone;
        goToOtp(phone, d.dev_code);
    })
    .catch(function(){ showPhoneErr('ქსელის შეცდომა'); btn.disabled=false; });
}

function showPhoneErr(msg) {
    document.getElementById('sendBtnIcon').textContent = '📤';
    document.getElementById('sendBtnTxt').textContent  = 'კოდის გაგზავნა';
    var el = document.querySelector('.auth-err') || (() => {
        var d = document.createElement('div'); d.className='auth-err'; d.style.marginBottom='16px';
        document.getElementById('stepPhone').prepend(d); return d;
    })();
    el.style.display = '';
    el.innerHTML = '⚠️ ' + msg;
}

function goToOtp(phone, devCode) {
    document.getElementById('stepPhone').style.display = 'none';
    document.getElementById('stepOtp').style.display   = '';
    document.getElementById('otpPhoneLabel').textContent = phone;
    document.getElementById('headSub').textContent = 'შეიყვანე SMS კოდი';

    if (devCode) { fillDevCode(devCode); } else { document.getElementById('d0').focus(); }
    startResendTimer();
}

function fillDevCode(code) {
    document.getElementById('devOtpCode').textContent = code;
    document.getElementById('devOtpBox').style.display = '';
    // Auto-fill boxes
    String(code).split('').forEach(function(digit, i) {
        var inp = document.getElementById('d'+i);
        if (inp) { inp.value = digit; inp.classList.add('filled'); }
    });
    document.getElementById('verifyBtn').disabled = false;
}

// ── OTP input behaviour ───────────────────────────────────────────────────────
function otpNext(i) {
    var d = document.getElementById('d'+i);
    var v = d.value.replace(/\D/g,'');
    d.value = v ? v[v.length-1] : '';
    d.classList.toggle('filled', !!d.value);

    if (d.value && i < 3) document.getElementById('d'+(i+1)).focus();

    var code = getOtpCode();
    document.getElementById('verifyBtn').disabled = code.length !== 4;
    document.getElementById('otpErr').style.display = 'none';
    if (code.length === 4) verifyOtp();
}

function otpKey(e, i) {
    if (e.key === 'Backspace') {
        var d = document.getElementById('d'+i);
        if (!d.value && i > 0) { document.getElementById('d'+(i-1)).focus(); }
    }
}

function getOtpCode() {
    return ['d0','d1','d2','d3'].map(function(id){ return document.getElementById(id).value; }).join('');
}

function clearOtp() {
    for (var i=0;i<4;i++) {
        var d=document.getElementById('d'+i);
        d.value=''; d.classList.remove('filled');
    }
    document.getElementById('d0').focus();
    document.getElementById('verifyBtn').disabled = true;
}

// ── Step 2: Verify ────────────────────────────────────────────────────────────
function verifyOtp() {
    var code = getOtpCode();
    if (code.length !== 4) return;

    var btn = document.getElementById('verifyBtn');
    btn.disabled = true;
    document.getElementById('verifyTxt').textContent = 'მოწმდება...';
    document.getElementById('otpErr').style.display = 'none';

    fetch(BASE + '/api/taxi/otp/verify', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({phone: currentPhone, code: code})
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (d.ok) {
            document.getElementById('verifyTxt').textContent = '✅ წარმატება!';
            window.location.href = d.redirect || (BASE+'/'+SLUG);
        } else {
            var err = document.getElementById('otpErr');
            err.textContent = '⚠️ ' + (d.error || 'არასწორი კოდი');
            err.style.display = '';
            clearOtp();
            btn.disabled = false;
            document.getElementById('verifyTxt').textContent = 'შესვლა';
        }
    })
    .catch(function(){
        var err = document.getElementById('otpErr');
        err.textContent = '⚠️ ქსელის შეცდომა';
        err.style.display = '';
        btn.disabled = false;
        document.getElementById('verifyTxt').textContent = 'შესვლა';
    });
}

function backToPhone() {
    document.getElementById('stepOtp').style.display   = 'none';
    document.getElementById('stepPhone').style.display = '';
    document.getElementById('headSub').textContent = 'შედი ან დარეგისტრირდი';
    clearOtp();
    if (resendInterval) clearInterval(resendInterval);
}

function resendOtp() {
    var phone = currentPhone;
    if (!phone) return;
    fetch(BASE + '/api/taxi/otp/send', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({phone: phone})
    }).then(function(r){return r.json();}).then(function(d){
        if (d.dev_code) { fillDevCode(d.dev_code); } else { clearOtp(); }
        startResendTimer();
    });
}

function startResendTimer() {
    var t = 60;
    var btn = document.getElementById('resendBtn');
    var span = document.getElementById('resendTimer');
    btn.disabled = true;
    if (resendInterval) clearInterval(resendInterval);
    resendInterval = setInterval(function(){
        t--;
        span.textContent = t;
        if (t <= 0) { clearInterval(resendInterval); btn.disabled=false; span.textContent=''; btn.textContent='ხელახლა გაგზავნა'; }
    }, 1000);
}

<?php if($step === 'otp' && $phone): ?>
// Page loaded at OTP step
startResendTimer();
setTimeout(function(){ document.getElementById('d0').focus(); }, 100);
<?php endif ?>
</script>
