<?php
$brand = $delivery->setting('brand_name','GoniDelivery');
$slug  = $delivery->setting('page_slug','delivery');
?>
<style>
.auth-wrap{min-height:calc(100vh - 80px);display:flex;align-items:center;justify-content:center;padding:24px 16px;background:linear-gradient(135deg,#0f172a,#1e1b4b)}
.auth-box{background:#fff;border-radius:24px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.35)}
.auth-head{background:linear-gradient(135deg,#f59e0b,#ef4444);padding:32px 28px;text-align:center;color:#fff}
.auth-body{padding:32px 28px}
.auth-label{font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;display:block}
.auth-input{width:100%;border:1.5px solid #e2e8f0;border-radius:12px;padding:14px 16px;font-size:15px;font-family:inherit;outline:none;transition:all .15s;box-sizing:border-box}
.auth-input:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.1)}
.auth-btn{width:100%;padding:15px;background:#f59e0b;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:8px}
.auth-btn:hover{background:#d97706}
.auth-btn:disabled{background:#fcd34d;cursor:not-allowed}
.otp-grid{display:flex;gap:10px;justify-content:center;margin:8px 0 4px}
.otp-digit{width:56px;height:64px;border:2px solid #e2e8f0;border-radius:14px;font-size:28px;font-weight:900;text-align:center;font-family:monospace;outline:none;transition:all .15s}
.otp-digit:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.1)}
.otp-digit.filled{border-color:#f59e0b;background:#fffbeb}
.dev-otp{background:#fef9c3;border:2px solid #f59e0b;border-radius:12px;padding:14px 16px;margin-bottom:16px;display:none;text-align:center}
.dev-otp-label{font-size:11px;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.dev-otp-code{font-size:36px;font-weight:900;font-family:monospace;color:#b45309;letter-spacing:10px}
.dev-otp-note{font-size:11px;color:#92400e;margin-top:6px;opacity:.7}
</style>

<div class="auth-wrap">
  <div class="auth-box">
    <div class="auth-head">
      <div style="font-size:52px;margin-bottom:8px">🛵</div>
      <div style="font-size:26px;font-weight:900"><?= e($brand) ?></div>
      <div id="headSub" style="font-size:13px;opacity:.75;margin-top:4px"><?= $step==='otp'?'შეიყვანე SMS კოდი':'შედი ან დარეგისტრირდი'?></div>
    </div>
    <div class="auth-body">
      <div id="stepPhone" <?= $step==='otp'?'style="display:none"':''?>>
        <div style="margin-bottom:16px">
          <label class="auth-label">📱 ტელეფონი</label>
          <input type="tel" id="phoneInput" class="auth-input" placeholder="+995 555 000 000" value="<?= e($phone)?>" oninput="onPhoneInput()" onkeydown="if(event.key==='Enter'){event.preventDefault();sendOtp()}">
        </div>
        <button class="auth-btn" id="sendBtn" onclick="sendOtp()" disabled><span>📤</span><span id="sendBtnTxt">კოდის გაგზავნა</span></button>
      </div>

      <div id="stepOtp" <?= $step!=='otp'?'style="display:none"':''?>>
        <div style="text-align:center;margin-bottom:16px;font-size:13px;color:#64748b">კოდი გაიგზავნა ნომერზე <strong id="otpPhone"><?= e($phone)?></strong></div>
        <div id="devOtp" class="dev-otp">
          <div class="dev-otp-label">🧪 TEST MODE · SMS კოდი</div>
          <div class="dev-otp-code" id="devCode">—</div>
          <div class="dev-otp-note">ყუთები ავტომატურად ივსება · პროდაქშენში APP_DEBUG=false</div>
        </div>
        <div class="otp-grid">
          <input class="otp-digit" type="tel" maxlength="1" id="d0" oninput="otpNext(0)" onkeydown="otpKey(event,0)">
          <input class="otp-digit" type="tel" maxlength="1" id="d1" oninput="otpNext(1)" onkeydown="otpKey(event,1)">
          <input class="otp-digit" type="tel" maxlength="1" id="d2" oninput="otpNext(2)" onkeydown="otpKey(event,2)">
          <input class="otp-digit" type="tel" maxlength="1" id="d3" oninput="otpNext(3)" onkeydown="otpKey(event,3)">
        </div>
        <div id="otpErr" style="display:none;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:10px 14px;color:#b91c1c;font-size:13px;margin:12px 0"></div>
        <button class="auth-btn" id="verifyBtn" onclick="verifyOtp()" style="margin-top:14px" disabled><span>✅</span><span id="verifyTxt">შესვლა</span></button>
        <div style="text-align:center;margin-top:14px;font-size:13px;color:#64748b">
          <button id="resendBtn" onclick="resendOtp()" style="background:none;border:none;color:#f59e0b;font-weight:700;cursor:pointer;font-family:inherit;font-size:13px" disabled>ხელახლა გაგზავნა (<span id="rtimer">60</span>წ)</button>
        </div>
        <button onclick="backToPhone()" style="display:block;width:100%;background:none;border:none;color:#64748b;font-size:13px;cursor:pointer;font-family:inherit;margin-top:12px;text-decoration:underline">← სხვა ნომერი</button>
      </div>
    </div>
  </div>
</div>

<script>
var BASE = <?= json_encode($base) ?>;
var SLUG = <?= json_encode($slug) ?>;
var curPhone = <?= json_encode($phone) ?>;
var resendIv = null;

function onPhoneInput(){ document.getElementById('sendBtn').disabled = document.getElementById('phoneInput').value.trim().length < 9; }
onPhoneInput();

function sendOtp(){
    var phone = document.getElementById('phoneInput').value.trim();
    if(!phone) return;
    document.getElementById('sendBtn').disabled = true;
    document.getElementById('sendBtnTxt').textContent = 'იგზავნება...';
    fetch(BASE+'/api/delivery/otp/send',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({phone:phone})})
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.ok){ document.getElementById('sendBtnTxt').textContent='კოდის გაგზავნა'; document.getElementById('sendBtn').disabled=false; return; }
            curPhone = phone;
            document.getElementById('otpPhone').textContent = phone;
            if(d.dev_code){ fillDevCode(d.dev_code); }
            document.getElementById('stepPhone').style.display='none';
            document.getElementById('stepOtp').style.display='';
            document.getElementById('headSub').textContent='შეიყვანე SMS კოდი';
            document.getElementById('d0').focus();
            startResendTimer();
        }).catch(function(){ document.getElementById('sendBtnTxt').textContent='კოდის გაგზავნა'; document.getElementById('sendBtn').disabled=false; });
}

function otpNext(i){ var d=document.getElementById('d'+i); var v=d.value.replace(/\D/g,''); d.value=v?v[v.length-1]:''; d.classList.toggle('filled',!!d.value); if(d.value&&i<3)document.getElementById('d'+(i+1)).focus(); var code=getCode(); document.getElementById('verifyBtn').disabled=code.length!==4; document.getElementById('otpErr').style.display='none'; if(code.length===4)verifyOtp(); }
function otpKey(e,i){ if(e.key==='Backspace'&&!document.getElementById('d'+i).value&&i>0)document.getElementById('d'+(i-1)).focus(); }
function getCode(){ return ['d0','d1','d2','d3'].map(function(id){return document.getElementById(id).value;}).join(''); }
function clearOtp(){ for(var i=0;i<4;i++){var d=document.getElementById('d'+i);d.value='';d.classList.remove('filled');} document.getElementById('verifyBtn').disabled=true; document.getElementById('d0').focus(); }

function verifyOtp(){
    var code=getCode(); if(code.length!==4)return;
    document.getElementById('verifyBtn').disabled=true;
    document.getElementById('verifyTxt').textContent='მოწმდება...';
    fetch(BASE+'/api/delivery/otp/verify',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({phone:curPhone,code:code})})
        .then(function(r){return r.json();})
        .then(function(d){
            if(d.ok){ window.location.href=d.redirect||(BASE+'/'+SLUG); }
            else{ var err=document.getElementById('otpErr'); err.textContent='⚠️ '+(d.error||'არასწორი კოდი'); err.style.display=''; clearOtp(); document.getElementById('verifyBtn').disabled=false; document.getElementById('verifyTxt').textContent='შესვლა'; }
        });
}

function backToPhone(){ document.getElementById('stepOtp').style.display='none'; document.getElementById('stepPhone').style.display=''; document.getElementById('headSub').textContent='შედი ან დარეგისტრირდი'; clearOtp(); if(resendIv)clearInterval(resendIv); }
function resendOtp(){ fetch(BASE+'/api/delivery/otp/send',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({phone:curPhone})}).then(function(r){return r.json();}).then(function(d){if(d.dev_code){fillDevCode(d.dev_code);}else{clearOtp();}startResendTimer();}); }

function fillDevCode(code){
    document.getElementById('devCode').textContent = code;
    document.getElementById('devOtp').style.display = '';
    // Auto-fill OTP boxes
    clearOtp();
    var digits = String(code).split('');
    digits.forEach(function(digit, i){
        var inp = document.getElementById('d'+i);
        if(inp){ inp.value = digit; inp.classList.add('filled'); }
    });
    document.getElementById('verifyBtn').disabled = false;
}
function startResendTimer(){ var t=60; var btn=document.getElementById('resendBtn'); var sp=document.getElementById('rtimer'); btn.disabled=true; if(resendIv)clearInterval(resendIv); resendIv=setInterval(function(){t--;sp.textContent=t;if(t<=0){clearInterval(resendIv);btn.disabled=false;btn.innerHTML='ხელახლა გაგზავნა';}},1000); }

<?php if($step==='otp'&&$phone): ?>startResendTimer();setTimeout(function(){document.getElementById('d0').focus();},100);<?php endif ?>
</script>
