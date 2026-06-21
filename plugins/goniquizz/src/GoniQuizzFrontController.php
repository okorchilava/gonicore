<?php
declare(strict_types=1);

namespace GoniQuizz;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

final class GoniQuizzFrontController
{
    public function __construct(
        private readonly GoniQuizzService $svc,
        private readonly string           $pluginDir,
    ) {}

    // ── GET /goniquizz/play?slug=X ─────────────────────────────────────────────

    public function play(Request $r): Response
    {
        $slug = trim((string) ($r->query('slug') ?? ''));
        $quiz = $slug ? $this->svc->quizBySlug($slug) : null;

        if (!$quiz || !(int)$quiz['active']) {
            return $this->page(
                'Quiz ვერ მოიძებნა',
                '<div class="gqz-card" style="text-align:center"><p style="color:#94a3b8;font-size:15px">ეს quiz ხელმისაწვდომი არ არის.</p></div>'
            );
        }

        // Cookie check — if already answered and retake not allowed
        $cookieKey    = 'goniquizz_' . (int)$quiz['id'];
        $submissionId = (int) ($_COOKIE[$cookieKey] ?? 0);

        if ($submissionId > 0 && !(int)$quiz['allow_retake']) {
            $sub = $this->svc->submission($submissionId);
            if ($sub) {
                $base = GoniQuizzService::getBasePath();
                return Response::redirect($base . '/goniquizz/result?id=' . $submissionId);
            }
        }

        $questions  = $this->svc->questions((int) $quiz['id']);
        $allOptions = [];
        foreach ($questions as $q) {
            $allOptions[(int)$q['id']] = $this->svc->options((int)$q['id']);
        }

        ob_start();
        include $this->pluginDir . '/views/front/play.php';
        return $this->page((string)$quiz['title'], (string) ob_get_clean());
    }

    // ── POST /goniquizz/submit ─────────────────────────────────────────────────

    public function submit(Request $r): Response
    {
        $base   = GoniQuizzService::getBasePath();
        $quizId = (int) $r->post('quiz_id', '0');
        if ($quizId <= 0) return Response::redirect($base . '/');

        $quiz = $this->svc->quiz($quizId);
        if (!$quiz || !(int)$quiz['active']) return Response::redirect($base . '/');

        // Parse answers: answers[question_id] = option_id  OR  answers[question_id][] = option_id
        $answers = [];
        $raw = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];
        foreach ($raw as $qid => $val) {
            $answers[(int)$qid] = is_array($val)
                ? array_map('intval', $val)
                : [(int)$val];
        }

        $submissionId = $this->svc->submit($quizId, $answers);

        // Set cookie (1 year)
        setcookie('goniquizz_' . $quizId, (string)$submissionId, time() + 31_536_000, '/', '', false, false);

        if ((int)$quiz['show_results'] === 0) {
            return $this->page(
                'Quiz დასრულდა',
                '<div class="gqz-card" style="text-align:center;padding:48px 24px">'
                . '<div style="font-size:56px;margin-bottom:16px">✅</div>'
                . '<h2 style="font-size:22px;font-weight:800;color:#1e293b;margin:0 0 8px">Quiz გავლილია!</h2>'
                . '<p style="color:#64748b;margin:0 0 24px">მადლობა მონაწილეობისთვის.</p>'
                . '<a href="' . htmlspecialchars($base . '/', ENT_QUOTES) . '" class="gqz-btn">← მთავარ გვერდზე</a>'
                . '</div>'
            );
        }

        return Response::redirect($base . '/goniquizz/result?id=' . $submissionId);
    }

    // ── GET /goniquizz/result?id=X ─────────────────────────────────────────────

    public function result(Request $r): Response
    {
        $id  = (int) ($r->query('id') ?? 0);
        $sub = $id ? $this->svc->submission($id) : null;

        if (!$sub) {
            return $this->page(
                'შედეგი ვერ მოიძებნა',
                '<div class="gqz-card" style="text-align:center"><p style="color:#94a3b8">შედეგი ვერ მოიძებნა.</p></div>'
            );
        }

        $quiz        = $this->svc->quiz((int)$sub['quiz_id']);
        $questions   = $quiz ? $this->svc->questions((int)$sub['quiz_id']) : [];
        $userAnswers = $this->svc->submissionAnswers($id);
        $pollResults = ($quiz && $quiz['type'] === 'poll')
            ? $this->svc->pollResults((int)$sub['quiz_id'])
            : [];
        $allOptions  = [];
        foreach ($questions as $q) {
            $allOptions[(int)$q['id']] = $this->svc->options((int)$q['id']);
        }

        ob_start();
        include $this->pluginDir . '/views/front/result.php';
        $title = 'შედეგი — ' . ($quiz['title'] ?? 'Quiz');
        return $this->page($title, (string) ob_get_clean());
    }

    // ── Shared HTML wrapper ────────────────────────────────────────────────────

    private function page(string $title, string $body): Response
    {
        $h    = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $base = GoniQuizzService::getBasePath();

        $html = '<!DOCTYPE html><html lang="ka"><head>'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $h($title) . '</title>'
            . '<link rel="preconnect" href="https://fonts.googleapis.com">'
            . '<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700;800&display=swap" rel="stylesheet">'
            . '<style>'
            . '*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}'
            . 'body{font-family:"Noto Sans Georgian",system-ui,sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;padding:24px 16px}'
            . '.gqz-wrap{max-width:680px;margin:0 auto}'
            . '.gqz-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);padding:28px 32px;margin-bottom:20px}'
            . '.gqz-title{font-size:22px;font-weight:800;color:#1e293b;margin-bottom:6px}'
            . '.gqz-desc{color:#64748b;font-size:14px;line-height:1.6;margin-bottom:0}'
            . '.gqz-question{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:22px 26px;margin-bottom:14px}'
            . '.gqz-q-num{font-size:11.5px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}'
            . '.gqz-q-text{font-size:15.5px;font-weight:700;color:#1e293b;line-height:1.5;margin-bottom:16px}'
            . '.gqz-opt{display:flex;align-items:center;gap:12px;padding:12px 16px;border:2px solid #e2e8f0;border-radius:10px;margin-bottom:8px;cursor:pointer;transition:border-color .15s,background .15s}'
            . '.gqz-opt:has(input:checked){border-color:#7c3aed;background:#f5f3ff}'
            . '.gqz-opt input{width:18px;height:18px;flex-shrink:0;accent-color:#7c3aed}'
            . '.gqz-opt-text{font-size:14px;color:#374151;line-height:1.4}'
            . '.gqz-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;border:none;border-radius:12px;padding:13px 28px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;transition:opacity .15s}'
            . '.gqz-btn:hover{opacity:.88}'
            . '.gqz-btn-ghost{background:transparent;color:#7c3aed;border:2px solid #7c3aed;border-radius:12px;padding:11px 24px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:background .15s}'
            . '.gqz-btn-ghost:hover{background:#f5f3ff}'
            . '.gqz-correct{border-color:#10b981!important;background:#f0fdf4!important}'
            . '.gqz-wrong{border-color:#ef4444!important;background:#fef2f2!important}'
            . '.gqz-correct .gqz-opt-text{color:#065f46;font-weight:600}'
            . '.gqz-wrong .gqz-opt-text{color:#991b1b}'
            . '.gqz-bar{height:8px;border-radius:8px;background:#e2e8f0;overflow:hidden;margin-top:4px}'
            . '.gqz-bar-fill{height:100%;background:linear-gradient(90deg,#7c3aed,#a855f7);border-radius:8px;transition:width .6s}'
            . '@media(max-width:520px){.gqz-card{padding:20px 16px}.gqz-question{padding:18px 16px}}'
            . '</style>'
            . '</head><body>'
            . '<div class="gqz-wrap">'
            . $body
            . '<div style="text-align:center;padding:16px 0;font-size:12px;color:#94a3b8">Powered by GoniQuizz</div>'
            . '</div></body></html>';

        return Response::html($html);
    }
}
