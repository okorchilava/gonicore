<?php
$advanceDays = max(1, (int)$svc->setting('advance_days', '30'));
?>
<style>
.ga-wrap{max-width:960px;margin:0 auto;padding:36px 20px}
.ga-hero{background:linear-gradient(135deg,#0f172a,#1e1b4b);color:#fff;padding:52px 24px;text-align:center;position:relative;overflow:hidden}
.ga-hero h1{font-size:clamp(24px,4vw,44px);font-weight:900;letter-spacing:-.5px;margin-bottom:10px}
.ga-hero p{color:#94a3b8;font-size:16px}
.ga-steps{display:flex;margin-bottom:28px;background:var(--surface);border-radius:12px;overflow:hidden;border:1px solid var(--border)}
.ga-step{flex:1;padding:12px 8px;text-align:center;font-size:13px;font-weight:600;color:var(--muted);border-right:1px solid var(--border);transition:all .2s;cursor:default}
.ga-step:last-child{border-right:none}
.ga-step.active{background:var(--accent);color:#fff}
.ga-step.done{background:#10b981;color:#fff}
.ga-step-num{display:block;font-size:10px;font-weight:800;margin-bottom:2px;opacity:.7}
.ga-services{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
.ga-svc-card{border:2px solid var(--border);border-radius:12px;padding:20px 14px;cursor:pointer;transition:all .2s;text-align:center;background:#fff}
.ga-svc-card:hover{border-color:var(--accent);box-shadow:0 4px 16px rgba(79,70,229,.1)}
.ga-svc-card.selected{border-color:var(--accent);background:#f5f3ff}
.ga-svc-dot{width:42px;height:42px;border-radius:10px;margin:0 auto 12px}
.ga-svc-name{font-weight:800;font-size:15px;margin-bottom:4px}
.ga-svc-meta{font-size:12px;color:var(--muted)}
.ga-staff-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px}
.ga-staff-card{border:2px solid var(--border);border-radius:10px;padding:18px 12px;cursor:pointer;transition:all .2s;text-align:center;background:#fff}
.ga-staff-card:hover{border-color:var(--accent)}
.ga-staff-card.selected{border-color:var(--accent);background:#f5f3ff}
.ga-staff-img{width:54px;height:54px;border-radius:50%;object-fit:cover;margin:0 auto 8px;display:block}
.ga-staff-av{width:54px;height:54px;border-radius:50%;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 8px}
.ga-date-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:5px;margin-bottom:20px}
.ga-date-btn{padding:10px 4px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;text-align:center;font-size:11.5px;transition:all .15s;background:#fff;line-height:1.3}
.ga-date-btn:hover{border-color:var(--accent);color:var(--accent)}
.ga-date-btn.selected{background:var(--accent);color:#fff;border-color:var(--accent)}
.ga-date-btn.today .ga-date-day{font-weight:900}
.ga-slots{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.ga-slot{padding:9px 18px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;font-size:14px;font-weight:700;transition:all .15s;background:#fff}
.ga-slot:hover{border-color:var(--accent);color:var(--accent)}
.ga-slot.selected{background:var(--accent);color:#fff;border-color:var(--accent)}
.ga-section{display:none}
.ga-section.visible{display:block}
.ga-nav{display:flex;gap:10px;margin-top:22px}
.ga-error{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 16px;font-size:13.5px;color:#dc2626;margin-bottom:20px;display:flex;gap:8px;align-items:center}
</style>

<div class="ga-hero">
    <svg style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:.04" xmlns="http://www.w3.org/2000/svg">
        <pattern id="gp" width="60" height="60" patternUnits="userSpaceOnUse">
            <path d="M60 0L0 60M30 0L0 30M60 30L30 60" stroke="#fff" stroke-width=".5"/>
        </pattern>
        <rect width="100%" height="100%" fill="url(#gp)"/>
    </svg>
    <h1>📅 <?= e($brandName) ?></h1>
    <p>Choose your service, staff, and time — done in seconds.</p>
</div>

<div class="ga-wrap">

<?php if ($error): ?>
<div class="ga-error">⚠️ <?= e($error) ?></div>
<?php endif ?>

<!-- Step indicator -->
<div class="ga-steps" id="stepNav">
    <div class="ga-step active" data-step="1"><span class="ga-step-num">Step 1</span>Service</div>
    <div class="ga-step" data-step="2"><span class="ga-step-num">Step 2</span>Staff</div>
    <div class="ga-step" data-step="3"><span class="ga-step-num">Step 3</span>Date & Time</div>
    <div class="ga-step" data-step="4"><span class="ga-step-num">Step 4</span>Details</div>
</div>

<!-- ── Step 1: Service ─────────────────────────────────────────────────────── -->
<div class="ga-section visible" id="step1">
    <h2 style="font-size:20px;font-weight:800;margin-bottom:16px">Choose a Service</h2>
    <?php if (empty($services)): ?>
    <div style="text-align:center;padding:60px 24px;color:var(--muted)">
        <div style="font-size:52px;margin-bottom:12px">💆</div>
        <p>No services are available yet. Please check back soon.</p>
    </div>
    <?php else: ?>
    <div class="ga-services" id="serviceGrid">
        <?php foreach ($services as $sv): ?>
        <div class="ga-svc-card"
             data-id="<?= (int)$sv['id'] ?>"
             data-name="<?= e($sv['name']) ?>"
             data-duration="<?= (int)$sv['duration_minutes'] ?>"
             data-price="<?= number_format((float)$sv['price'], 2) ?>"
             onclick="selectService(this)">
            <div class="ga-svc-dot" style="background:<?= e($sv['color']) ?>"></div>
            <div class="ga-svc-name"><?= e($sv['name']) ?></div>
            <div class="ga-svc-meta"><?= (int)$sv['duration_minutes'] ?> min · <?= number_format((float)$sv['price'], 2) ?></div>
            <?php if ($sv['description']): ?>
            <div style="margin-top:8px;font-size:12px;color:var(--muted);line-height:1.5"><?= e(mb_strimwidth((string)$sv['description'], 0, 90, '…')) ?></div>
            <?php endif ?>
        </div>
        <?php endforeach ?>
    </div>
    <div class="ga-nav">
        <button class="btn btn-primary" id="step1Next" disabled onclick="goStep(2)">Next: Choose Staff →</button>
    </div>
    <?php endif ?>
</div>

<!-- ── Step 2: Staff ───────────────────────────────────────────────────────── -->
<div class="ga-section" id="step2">
    <h2 style="font-size:20px;font-weight:800;margin-bottom:16px">Choose a Staff Member</h2>
    <div id="staffLoading" style="color:var(--muted);padding:24px 0;font-size:14px">Loading available staff…</div>
    <div class="ga-staff-grid" id="staffGrid"></div>
    <div class="ga-nav">
        <button class="btn btn-ghost" onclick="goStep(1)">← Back</button>
        <button class="btn btn-primary" id="step2Next" disabled onclick="goStep(3)">Next: Choose Date & Time →</button>
    </div>
</div>

<!-- ── Step 3: Date & Time ─────────────────────────────────────────────────── -->
<div class="ga-section" id="step3">
    <h2 style="font-size:20px;font-weight:800;margin-bottom:16px">Choose Date & Time</h2>
    <div style="font-weight:700;font-size:14px;margin-bottom:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Select a Date</div>
    <div class="ga-date-grid" id="dateGrid"></div>
    <div id="slotsSection" style="display:none">
        <div style="font-weight:700;font-size:14px;margin-bottom:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Available Times <span id="slotDateLabel" style="font-weight:400;text-transform:none;letter-spacing:0"></span></div>
        <div id="slotsLoading" style="color:var(--muted);font-size:14px">Loading slots…</div>
        <div class="ga-slots" id="slotsGrid"></div>
    </div>
    <div class="ga-nav">
        <button class="btn btn-ghost" onclick="goStep(2)">← Back</button>
        <button class="btn btn-primary" id="step3Next" disabled onclick="goStep(4)">Next: Your Details →</button>
    </div>
</div>

<!-- ── Step 4: Details ─────────────────────────────────────────────────────── -->
<div class="ga-section" id="step4">
    <h2 style="font-size:20px;font-weight:800;margin-bottom:8px">Your Details</h2>
    <div id="summaryBox" style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:13.5px;line-height:2"></div>
    <form method="POST" action="<?= e($base) ?>/<?= e($bookSlug) ?>/process" id="bookForm" style="max-width:480px">
        <input type="hidden" name="service_id"       id="fi_service">
        <input type="hidden" name="staff_id"         id="fi_staff">
        <input type="hidden" name="appointment_date" id="fi_date">
        <input type="hidden" name="start_time"       id="fi_time">
        <div style="display:flex;flex-direction:column;gap:14px">
            <div>
                <label class="form-label">Full Name <span style="color:#ef4444">*</span></label>
                <input type="text" name="customer_name" class="form-input" required placeholder="Your full name">
            </div>
            <div>
                <label class="form-label">Email <span style="color:#ef4444">*</span></label>
                <input type="email" name="customer_email" class="form-input" required placeholder="your@email.com">
            </div>
            <div>
                <label class="form-label">Phone</label>
                <input type="tel" name="customer_phone" class="form-input" placeholder="+995 …">
            </div>
            <div>
                <label class="form-label">Note (optional)</label>
                <textarea name="customer_note" class="form-input" rows="3" placeholder="Any special requests or notes…"></textarea>
            </div>
        </div>
        <div class="ga-nav">
            <button type="button" class="btn btn-ghost" onclick="goStep(3)">← Back</button>
            <button type="submit" class="btn btn-primary" style="padding:11px 28px;font-size:15px">Confirm Booking →</button>
        </div>
    </form>
</div>

</div>

<script>
var state = {serviceId:0, serviceName:'', staffId:0, staffName:'', date:'', time:''};
var advanceDays = <?= (int)$advanceDays ?>;
var apiBase = '<?= e($base) ?>';

function selectService(el) {
    document.querySelectorAll('.ga-svc-card').forEach(function(c){c.classList.remove('selected')});
    el.classList.add('selected');
    state.serviceId   = el.dataset.id;
    state.serviceName = el.dataset.name + ' (' + el.dataset.duration + ' min · ' + el.dataset.price + ')';
    state.staffId = 0; state.staffName = '';
    document.getElementById('step1Next').disabled = false;
}

function goStep(n) {
    document.querySelectorAll('.ga-section').forEach(function(s){s.classList.remove('visible')});
    document.getElementById('step'+n).classList.add('visible');
    document.querySelectorAll('.ga-step').forEach(function(s){
        var sn = parseInt(s.dataset.step);
        s.classList.remove('active','done');
        if (sn < n) s.classList.add('done');
        else if (sn === n) s.classList.add('active');
    });
    if (n === 2) loadStaff();
    if (n === 3) buildDateGrid();
    if (n === 4) buildSummary();
    window.scrollTo({top:0, behavior:'smooth'});
}

function loadStaff() {
    var grid = document.getElementById('staffGrid');
    var loading = document.getElementById('staffLoading');
    grid.innerHTML = '';
    loading.style.display = 'block';
    state.staffId = 0; state.staffName = '';
    document.getElementById('step2Next').disabled = true;

    fetch(apiBase + '/api/appointment/staff/' + state.serviceId)
        .then(function(r){ return r.json(); })
        .then(function(staff) {
            loading.style.display = 'none';
            if (!staff.length) {
                grid.innerHTML = '<p style="color:var(--muted);padding:12px 0;font-size:14px">No staff members are currently available for this service.</p>';
                return;
            }
            staff.forEach(function(s) {
                var div = document.createElement('div');
                div.className = 'ga-staff-card';
                div.dataset.id   = s.id;
                div.dataset.name = s.name;
                var img = s.image
                    ? '<img src="'+s.image+'" class="ga-staff-img" alt="">'
                    : '<div class="ga-staff-av">👤</div>';
                div.innerHTML = img
                    + '<div style="font-weight:700;font-size:14px">'+escHtml(s.name)+'</div>'
                    + (s.title ? '<div style="font-size:12px;color:var(--muted);margin-top:2px">'+escHtml(s.title)+'</div>' : '');
                div.onclick = function(){ selectStaff(this); };
                grid.appendChild(div);
            });
        })
        .catch(function(){
            loading.style.display = 'none';
            grid.innerHTML = '<p style="color:#ef4444">Failed to load staff. Please try again.</p>';
        });
}

function selectStaff(el) {
    document.querySelectorAll('.ga-staff-card').forEach(function(c){c.classList.remove('selected')});
    el.classList.add('selected');
    state.staffId   = el.dataset.id;
    state.staffName = el.dataset.name;
    document.getElementById('step2Next').disabled = false;
}

function buildDateGrid() {
    var grid = document.getElementById('dateGrid');
    grid.innerHTML = '';
    state.date = ''; state.time = '';
    document.getElementById('step3Next').disabled = true;
    document.getElementById('slotsSection').style.display = 'none';

    var today   = new Date();
    var dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var monNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    for (var i = 0; i < advanceDays; i++) {
        var d = new Date(today.getFullYear(), today.getMonth(), today.getDate() + i);
        var y  = d.getFullYear();
        var m  = String(d.getMonth()+1).padStart(2,'0');
        var dd = String(d.getDate()).padStart(2,'0');
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ga-date-btn' + (i === 0 ? ' today' : '');
        btn.dataset.date = y+'-'+m+'-'+dd;
        btn.innerHTML =
            '<div style="font-size:10px;text-transform:uppercase;opacity:.6">'+dayNames[d.getDay()]+'</div>'+
            '<div class="ga-date-day" style="font-size:17px;font-weight:700;line-height:1.2">'+d.getDate()+'</div>'+
            '<div style="font-size:10px;opacity:.6">'+monNames[d.getMonth()]+'</div>';
        btn.onclick = function(){ selectDate(this); };
        grid.appendChild(btn);
    }
}

function selectDate(el) {
    document.querySelectorAll('.ga-date-btn').forEach(function(b){b.classList.remove('selected')});
    el.classList.add('selected');
    state.date = el.dataset.date;
    state.time = '';
    document.getElementById('step3Next').disabled = true;
    document.getElementById('slotDateLabel').textContent = '— '+state.date;
    loadSlots();
}

function loadSlots() {
    var section = document.getElementById('slotsSection');
    var loading = document.getElementById('slotsLoading');
    var grid    = document.getElementById('slotsGrid');
    section.style.display = 'block';
    loading.style.display = 'block';
    grid.innerHTML = '';

    fetch(apiBase+'/api/appointment/slots/'+state.serviceId+'/'+state.staffId+'/'+state.date)
        .then(function(r){ return r.json(); })
        .then(function(slots) {
            loading.style.display = 'none';
            if (!slots.length) {
                grid.innerHTML = '<p style="color:var(--muted);font-size:13px;padding:4px 0">No available slots on this date. Try a different day.</p>';
                return;
            }
            slots.forEach(function(t) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ga-slot';
                btn.textContent = t;
                btn.dataset.time = t;
                btn.onclick = function(){ selectSlot(this); };
                grid.appendChild(btn);
            });
        })
        .catch(function(){
            loading.style.display = 'none';
            grid.innerHTML = '<p style="color:#ef4444">Failed to load slots.</p>';
        });
}

function selectSlot(el) {
    document.querySelectorAll('.ga-slot').forEach(function(b){b.classList.remove('selected')});
    el.classList.add('selected');
    state.time = el.dataset.time;
    document.getElementById('step3Next').disabled = false;
}

function buildSummary() {
    document.getElementById('fi_service').value = state.serviceId;
    document.getElementById('fi_staff').value   = state.staffId;
    document.getElementById('fi_date').value    = state.date;
    document.getElementById('fi_time').value    = state.time;
    document.getElementById('summaryBox').innerHTML =
        '<strong>Booking Summary</strong><br>'+
        '🔧 <strong>Service:</strong> '+escHtml(state.serviceName)+'<br>'+
        '👤 <strong>Staff:</strong> '+escHtml(state.staffName)+'<br>'+
        '📅 <strong>Date:</strong> '+escHtml(state.date)+' at <strong>'+escHtml(state.time)+'</strong>';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
