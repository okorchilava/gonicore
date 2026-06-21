<!DOCTYPE html>
<html lang="ka">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Driver Login</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:#0d1117;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.box{background:#161b22;border:1px solid #21262d;border-radius:20px;width:100%;max-width:380px;overflow:hidden}
.head{background:linear-gradient(135deg,#238636,#2ea043);padding:32px 24px;text-align:center;color:#fff}
.head-icon{font-size:48px;margin-bottom:10px}
.head-title{font-size:22px;font-weight:900}
.head-sub{font-size:13px;opacity:.8;margin-top:4px}
.body{padding:28px}
.err{background:#3d1f1f;border:1px solid #da3633;border-radius:8px;padding:11px 14px;color:#f85149;font-size:13px;margin-bottom:18px}
.field{margin-bottom:16px}
.lbl{display:block;font-size:12px;font-weight:700;color:#8b949e;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.inp{width:100%;border:1.5px solid #30363d;border-radius:10px;padding:12px 14px;font-size:14px;font-family:inherit;background:#0d1117;color:#f0f6fc;outline:none;transition:border-color .15s}
.inp:focus{border-color:#238636}
.btn{width:100%;padding:14px;background:#238636;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:background .15s}
.btn:hover{background:#2ea043}
.note{text-align:center;margin-top:16px;font-size:12.5px;color:#484f58}
</style>
</head>
<body>
<div class="box">
    <div class="head">
        <div class="head-icon">🧑‍✈️</div>
        <div class="head-title">Driver Portal</div>
        <div class="head-sub">Sign in to start accepting rides</div>
    </div>
    <div class="body">
        <?php if(!empty($error)): ?>
        <div class="err">⚠️ <?= htmlspecialchars($error,ENT_QUOTES) ?></div>
        <?php endif ?>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'],ENT_QUOTES) ?>">
            <div class="field">
                <label class="lbl">📱 Phone Number</label>
                <input type="tel" name="phone" class="inp" placeholder="+995 555 000 000" required autofocus>
            </div>
            <div class="field">
                <label class="lbl">🔒 Password</label>
                <input type="password" name="password" class="inp" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn">Sign In →</button>
        </form>
        <div class="note">Contact your admin if you forgot your password.</div>
    </div>
</div>
</body>
</html>
