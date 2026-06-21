<?php

declare(strict_types=1);

namespace GCLiveChat;

use GCLiveChat\Ai\AiResponder;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Logging\ErrorLogger;

/**
 * Visitor side of GC Live Chat: the floating widget (injected via theme.footer)
 * and its JSON endpoints. A conversation's secret token authorises the visitor's
 * actions on that conversation, so the public endpoints need no login/CSRF.
 */
final class LiveChatFrontend
{
    public function __construct(
        private readonly ChatService $chat,
        private readonly AiResponder $responder,
    ) {}

    private function t(): callable
    {
        return gc_plugin_translator(dirname(__DIR__));
    }

    private function endpoint(string $path): string
    {
        $base = rtrim((string) gc_setting('site_url', ''), '/');
        return ($base !== '' ? $base : '') . '/gc-chat/' . $path;
    }

    /** Resolve + authorise the conversation from its token. */
    private function auth(Request $request): ?array
    {
        $token = (string) ($request->post('token', '') ?: $request->query('token', ''));
        return $this->chat->byToken($token);
    }

    // ── Endpoints ───────────────────────────────────────────────────────────────

    public function start(Request $request): Response
    {
        $t   = $this->t();
        $ip  = (string) ($request->server('REMOTE_ADDR', '') ?? '');
        $row = $this->chat->create($ip);

        // No AI configured → route straight to a human queue.
        $aiOn = $this->responder->isConfigured();
        if (!$aiOn) {
            $this->chat->setStatus((int) $row['id'], 'waiting');
        }

        $greeting = trim((string) gc_setting('livechat_greeting', ''));
        if ($greeting === '') {
            $greeting = $aiOn ? $t('front.greeting') : $t('front.greeting_human');
        }
        $mid = $this->chat->addMessage((int) $row['id'], 'ai', $greeting);

        return Response::json([
            'ok'       => true,
            'token'    => $row['token'],
            'status'   => $aiOn ? 'ai' : 'waiting',
            'messages' => [['id' => $mid, 'sender' => 'ai', 'body' => $greeting]],
        ]);
    }

    public function send(Request $request): Response
    {
        $conv = $this->auth($request);
        if ($conv === null) {
            return Response::json(['ok' => false], 403);
        }
        $body = trim((string) $request->post('body', ''));
        if ($body === '') {
            return Response::json(['ok' => false], 422);
        }
        $body   = mb_substr($body, 0, 2000);
        $convId = (int) $conv['id'];
        $selfId = $this->chat->addMessage($convId, 'visitor', $body);

        $replies = [];
        // The AI only answers while the conversation is still in AI mode.
        if ($conv['status'] === 'ai' && $this->responder->isConfigured()) {
            try {
                $reply = $this->responder->reply($this->chat->history($convId, 20));
                if ($reply !== '') {
                    $rid = $this->chat->addMessage($convId, 'ai', $reply);
                    $replies[] = ['id' => $rid, 'sender' => 'ai', 'body' => $reply];
                }
            } catch (\Throwable $e) {
                ErrorLogger::instance()?->logThrowable($e);
                $msg = ($this->t())('front.ai_unavailable');
                $rid = $this->chat->addMessage($convId, 'system', $msg);
                $this->chat->setStatus($convId, 'waiting');
                if ($conv['summary'] === '') {
                    $this->chat->setSummary($convId, $body, '');
                }
                $replies[] = ['id' => $rid, 'sender' => 'system', 'body' => $msg];
            }
        }

        return Response::json(['ok' => true, 'self_id' => $selfId, 'messages' => $replies]);
    }

    public function poll(Request $request): Response
    {
        $conv = $this->auth($request);
        if ($conv === null) {
            return Response::json(['ok' => false], 403);
        }
        $after = max(0, (int) $request->query('after', '0'));
        $msgs  = $this->chat->messagesAfter((int) $conv['id'], $after);

        return Response::json([
            'ok'       => true,
            'status'   => (string) $conv['status'],
            'messages' => array_map(static fn (array $m): array => [
                'id' => (int) $m['id'], 'sender' => (string) $m['sender'], 'body' => (string) $m['body'],
            ], $msgs),
        ]);
    }

    public function requestOperator(Request $request): Response
    {
        $conv = $this->auth($request);
        if ($conv === null) {
            return Response::json(['ok' => false], 403);
        }
        $convId = (int) $conv['id'];
        $t      = $this->t();

        if ($conv['status'] !== 'operator') {
            $this->chat->setStatus($convId, 'waiting');
            // Summarise the request from the first visitor message for the operator.
            if ($conv['summary'] === '') {
                $first = '';
                foreach ($this->chat->history($convId, 30) as $h) {
                    if ((string) $h['sender'] === 'visitor') { $first = (string) $h['body']; break; }
                }
                if ($first !== '') {
                    $this->chat->setSummary($convId, $first, '');
                }
            }
            $msg = $t('front.connecting');
            $mid = $this->chat->addMessage($convId, 'system', $msg);
            return Response::json(['ok' => true, 'messages' => [['id' => $mid, 'sender' => 'system', 'body' => $msg]]]);
        }

        return Response::json(['ok' => true, 'messages' => []]);
    }

    // ── Widget (theme.footer) ─────────────────────────────────────────────────────

    public function widget(): void
    {
        if ((string) gc_setting('livechat_enabled', '0') !== '1') {
            return;
        }
        $t     = $this->t();
        $color = (string) gc_setting('livechat_color', '#4f46e5');
        if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            $color = '#4f46e5';
        }
        $title    = (string) (gc_setting('livechat_title', '') ?: $t('front.title'));
        $subtitle = $t('front.subtitle');

        $cfg = json_encode([
            'start'   => $this->endpoint('start'),
            'send'    => $this->endpoint('send'),
            'poll'    => $this->endpoint('poll'),
            'human'   => $this->endpoint('request-operator'),
            'i18n'    => [
                'placeholder' => $t('front.placeholder'),
                'send'        => $t('front.send'),
                'human'       => $t('front.talk_human'),
                'operator'    => $t('front.operator_joined'),
                'error'       => $t('front.error'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        echo "\n<!-- GC Live Chat -->\n<style>";
        echo <<<CSS
#gclc-btn{position:fixed;bottom:22px;right:22px;width:60px;height:60px;border-radius:50%;background:{$color};color:#fff;border:none;cursor:pointer;box-shadow:0 10px 30px -8px {$color};display:flex;align-items:center;justify-content:center;z-index:99998;transition:transform .2s}
#gclc-btn:hover{transform:scale(1.06)}
#gclc-btn svg{width:28px;height:28px}
#gclc-panel{position:fixed;bottom:94px;right:22px;width:360px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 130px);background:#fff;border-radius:18px;box-shadow:0 24px 60px -12px rgba(15,23,42,.35);display:none;flex-direction:column;overflow:hidden;z-index:99998;font-family:system-ui,-apple-system,"Noto Sans Georgian",sans-serif}
#gclc-panel.gclc-open{display:flex}
.gclc-head{background:{$color};color:#fff;padding:16px 18px}
.gclc-head h4{margin:0;font-size:16px;font-weight:700}
.gclc-head p{margin:2px 0 0;font-size:12px;opacity:.85}
.gclc-body{flex:1;overflow-y:auto;padding:16px;background:#f8fafc;display:flex;flex-direction:column;gap:10px}
.gclc-msg{max-width:80%;padding:10px 13px;border-radius:14px;font-size:14px;line-height:1.45;white-space:pre-wrap;word-wrap:break-word}
.gclc-visitor{align-self:flex-end;background:{$color};color:#fff;border-bottom-right-radius:4px}
.gclc-ai,.gclc-operator{align-self:flex-start;background:#fff;color:#1f2937;border:1px solid #e5e7eb;border-bottom-left-radius:4px}
.gclc-system{align-self:center;background:#eef2ff;color:#4338ca;font-size:12px;padding:6px 12px;border-radius:10px;text-align:center;max-width:90%}
.gclc-foot{border-top:1px solid #e5e7eb;padding:10px;background:#fff}
.gclc-human{display:block;width:100%;background:none;border:none;color:{$color};font-size:12px;font-weight:600;cursor:pointer;padding:4px;margin-bottom:6px}
.gclc-human:hover{text-decoration:underline}
.gclc-inrow{display:flex;gap:8px;align-items:flex-end}
.gclc-in{flex:1;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;font-size:14px;font-family:inherit;resize:none;max-height:90px;outline:none}
.gclc-in:focus{border-color:{$color}}
.gclc-send{background:{$color};color:#fff;border:none;border-radius:12px;width:42px;height:42px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
.gclc-send svg{width:18px;height:18px}
CSS;
        echo "</style>\n";

        echo '<button id="gclc-btn" aria-label="' . $e($title) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></button>';

        echo '<div id="gclc-panel"><div class="gclc-head"><h4>' . $e($title) . '</h4><p>' . $e($subtitle) . '</p></div>'
           . '<div class="gclc-body" id="gclc-body"></div>'
           . '<div class="gclc-foot">'
           . '<button class="gclc-human" id="gclc-human">' . $e($t('front.talk_human')) . '</button>'
           . '<div class="gclc-inrow">'
           . '<textarea class="gclc-in" id="gclc-in" rows="1" placeholder="' . $e($t('front.placeholder')) . '"></textarea>'
           . '<button class="gclc-send" id="gclc-send" aria-label="' . $e($t('front.send')) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg></button>'
           . '</div></div></div>';

        echo "<script>\nwindow.GCLC=" . $cfg . ";\n";
        echo <<<'JS'
(function(){
  var C=window.GCLC, KEY='gclcToken', IDK='gclcLastId';
  var btn=document.getElementById('gclc-btn'), panel=document.getElementById('gclc-panel');
  var body=document.getElementById('gclc-body'), input=document.getElementById('gclc-in');
  var sendBtn=document.getElementById('gclc-send'), humanBtn=document.getElementById('gclc-human');
  var token=localStorage.getItem(KEY)||'', lastId=parseInt(localStorage.getItem(IDK)||'0',10)||0;
  var status='ai', started=false, polling=null, sawOperator=false;

  function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
  function add(m){
    var el=document.createElement('div');
    el.className='gclc-msg gclc-'+(m.sender||'ai');
    el.innerHTML=esc(m.body).replace(/\n/g,'<br>');
    body.appendChild(el); body.scrollTop=body.scrollHeight;
  }
  function ingest(list){ (list||[]).forEach(function(m){ add(m); if(m.id>lastId){lastId=m.id;localStorage.setItem(IDK,String(lastId));} }); }
  function post(url,data){
    var fd=new FormData(); for(var k in data){fd.append(k,data[k]);}
    return fetch(url,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd}).then(function(r){return r.json();});
  }
  function setStatus(s){ if(s){status=s;} if(status==='operator'&&!sawOperator){sawOperator=true;add({sender:'system',body:C.i18n.operator});} }

  function startConv(){
    if(started)return Promise.resolve(); started=true;
    return post(C.start,{}).then(function(d){
      if(!d||!d.ok)throw 0;
      token=d.token; localStorage.setItem(KEY,token);
      setStatus(d.status); ingest(d.messages); startPolling();
    }).catch(function(){started=false;});
  }
  function open(){
    panel.classList.add('gclc-open');
    (token?Promise.resolve():startConv()).then(function(){ if(token){pollNow();startPolling();} input.focus(); });
  }
  function close(){ panel.classList.remove('gclc-open'); }
  function send(){
    var v=input.value.trim(); if(!v)return;
    input.value=''; input.style.height='auto';
    add({sender:'visitor',body:v});
    var go=token?Promise.resolve():startConv();
    go.then(function(){
      return post(C.send,{token:token,body:v});
    }).then(function(d){
      if(!d||!d.ok)throw 0;
      if(d.self_id&&d.self_id>lastId){lastId=d.self_id;localStorage.setItem(IDK,String(lastId));}
      ingest(d.messages);
    }).catch(function(){ add({sender:'system',body:C.i18n.error}); });
  }
  function pollNow(){
    if(!token)return;
    fetch(C.poll+'?token='+encodeURIComponent(token)+'&after='+lastId,{headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json();}).then(function(d){ if(d&&d.ok){setStatus(d.status);ingest(d.messages);} }).catch(function(){});
  }
  function startPolling(){ if(polling)return; polling=setInterval(pollNow,4000); }

  btn.addEventListener('click',function(){ panel.classList.contains('gclc-open')?close():open(); });
  sendBtn.addEventListener('click',send);
  input.addEventListener('keydown',function(e){ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });
  input.addEventListener('input',function(){ input.style.height='auto'; input.style.height=Math.min(90,input.scrollHeight)+'px'; });
  humanBtn.addEventListener('click',function(){
    if(!token){ open(); return; }
    post(C.human,{token:token}).then(function(d){ if(d&&d.ok)ingest(d.messages); });
  });
  // Resume an existing conversation quietly in the background.
  if(token){ startPolling(); }
})();
JS;
        echo "\n</script>\n";
    }
}
