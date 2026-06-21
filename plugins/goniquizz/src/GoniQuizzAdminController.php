<?php
declare(strict_types=1);

namespace GoniQuizz;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GoniQuizzAdminController
{
    public function __construct(
        private readonly GoniQuizzService $svc,
        private readonly QueryBuilder     $qb,
        private readonly LoginService     $auth,
        private readonly HookManager      $hooks,
        private readonly string           $siteName = 'GoniCore',
    ) {}

    // ── Quizzes list / dashboard ───────────────────────────────────────────────

    public function quizzes(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('quizzes', [
            'base'   => $r->basePath(),
            'quizzes' => $this->svc->enrichedQuizzes(),
            'stats'  => $this->svc->overallStats(),
            'saved'  => $r->query('saved') === '1',
        ]);
    }

    // ── Quiz form ──────────────────────────────────────────────────────────────

    public function quizForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = $r->query('id') ? (int) $r->query('id') : null;
        $quiz = $id ? $this->svc->quiz($id) : null;
        return $this->renderPage('quiz_form', [
            'base'   => $r->basePath(),
            'quiz'   => $quiz,
            'isEdit' => $id !== null,
        ]);
    }

    public function quizSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = $r->post('id') ? (int) $r->post('id') : null;

        $title = trim((string) $r->post('title', ''));
        $slug  = $this->slugify(trim((string) $r->post('slug', '')));
        if ($slug === '') $slug = $this->slugify($title);

        $data = [
            'title'        => $title,
            'slug'         => $slug,
            'description'  => trim((string) $r->post('description', '')),
            'type'         => in_array($r->post('type'), ['graded', 'poll'], true)
                              ? (string) $r->post('type') : 'graded',
            'show_results' => (string) $r->post('show_results', '0') === '1' ? 1 : 0,
            'allow_retake' => (string) $r->post('allow_retake', '0') === '1' ? 1 : 0,
            'active'       => (string) $r->post('active', '0') === '1' ? 1 : 0,
        ];

        $savedId = $this->svc->saveQuiz($data, $id);
        return Response::redirect($r->basePath() . '/manage/goniquizz/questions?quiz_id=' . $savedId . '&saved=1');
    }

    public function quizDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->post('id', '0');
        if ($id > 0) $this->svc->deleteQuiz($id);
        return Response::redirect($r->basePath() . '/manage/goniquizz');
    }

    public function quizToggle(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id  = (int) $r->post('id',     '0');
        $cur = (int) $r->post('active', '0');
        if ($id > 0) $this->svc->saveQuiz(['active' => $cur ? 0 : 1], $id);
        return Response::redirect($r->basePath() . '/manage/goniquizz');
    }

    // ── Questions ──────────────────────────────────────────────────────────────

    public function questions(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $quizId = (int) ($r->query('quiz_id') ?? 0);
        $quiz   = $quizId ? $this->svc->quiz($quizId) : null;
        if (!$quiz) return Response::redirect($r->basePath() . '/manage/goniquizz');

        return $this->renderPage('questions', [
            'base'      => $r->basePath(),
            'quiz'      => $quiz,
            'questions' => $this->svc->enrichedQuestions($quizId),
            'saved'     => $r->query('saved') === '1',
        ]);
    }

    public function questionForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id      = $r->query('id')      ? (int) $r->query('id')      : null;
        $quizId  = $r->query('quiz_id') ? (int) $r->query('quiz_id') : null;
        $question = $id ? $this->svc->question($id) : null;
        $options  = $id ? $this->svc->options($id)  : [];
        $quiz     = $quizId ? $this->svc->quiz($quizId) : ($question ? $this->svc->quiz((int)$question['quiz_id']) : null);

        return $this->renderPage('question_form', [
            'base'     => $r->basePath(),
            'question' => $question,
            'options'  => $options,
            'quiz'     => $quiz,
            'isEdit'   => $id !== null,
        ]);
    }

    public function questionSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id     = $r->post('id')      ? (int) $r->post('id')      : null;
        $quizId = (int) $r->post('quiz_id', '0');

        $data = [
            'quiz_id'    => $quizId,
            'question'   => trim((string) $r->post('question', '')),
            'type'       => in_array($r->post('type'), ['single', 'multiple'], true)
                            ? (string) $r->post('type') : 'single',
            'sort_order' => (int) $r->post('sort_order', '0'),
            'active'     => 1,
        ];

        $qid = $this->svc->saveQuestion($data, $id);

        // Save options
        $rawOptions = is_array($_POST['options'] ?? null) ? $_POST['options'] : [];
        $this->svc->saveOptions($qid, $rawOptions);

        return Response::redirect($r->basePath() . '/manage/goniquizz/questions?quiz_id=' . $quizId . '&saved=1');
    }

    public function questionDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id     = (int) $r->post('id',      '0');
        $quizId = (int) $r->post('quiz_id', '0');
        if ($id > 0) $this->svc->deleteQuestion($id);
        return Response::redirect($r->basePath() . '/manage/goniquizz/questions?quiz_id=' . $quizId);
    }

    // ── Results ────────────────────────────────────────────────────────────────

    public function results(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $quizId = (int) ($r->query('quiz_id') ?? 0);
        $quiz   = $quizId ? $this->svc->quiz($quizId) : null;
        if (!$quiz) return Response::redirect($r->basePath() . '/manage/goniquizz');

        return $this->renderPage('results', [
            'base'        => $r->basePath(),
            'quiz'        => $quiz,
            'stats'       => $this->svc->quizStats($quizId),
            'submissions' => $this->svc->recentSubmissions($quizId),
            'pollResults' => $quiz['type'] === 'poll' ? $this->svc->pollResults($quizId) : [],
            'questions'   => $this->svc->questions($quizId),
            'cleared'     => $r->query('cleared') === '1',
        ]);
    }

    public function resultsClear(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $quizId = (int) $r->post('quiz_id', '0');
        if ($quizId > 0) $this->svc->clearResults($quizId);
        return Response::redirect($r->basePath() . '/manage/goniquizz/results?quiz_id=' . $quizId . '&cleared=1');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function slugify(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? $s;
        return trim($s, '-');
    }

    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/login');
        }
        return null;
    }

    /** @param array<string,mixed> $data */
    private function renderPage(string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        $base     = $data['base'] ?? '';
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

        $notifList       = [];
        $notifUnread     = 0;
        $panelLangs      = [];
        $currentLangCode = 'en';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/admin/' . $view . '.php';
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $themeDir . '/manage/layout.php';
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
