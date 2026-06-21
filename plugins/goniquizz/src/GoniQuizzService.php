<?php
declare(strict_types=1);

namespace GoniQuizz;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

final class GoniQuizzService
{
    private static ?self $instance  = null;
    private static string $basePath = '';

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $conn,
    ) {}

    public static function register(self $s): void       { self::$instance  = $s; }
    public static function getInstance(): ?self          { return self::$instance; }
    public static function setBasePath(string $b): void  { self::$basePath  = $b; }
    public static function getBasePath(): string         { return self::$basePath; }

    // ── Quiz CRUD ──────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> enriched with question_count + attempt_count */
    public function enrichedQuizzes(): array
    {
        $rows = $this->conn->pdo()->query("
            SELECT q.*,
                   (SELECT COUNT(*) FROM goniquizz_questions WHERE quiz_id = q.id AND active = 1) AS question_count,
                   (SELECT COUNT(*) FROM goniquizz_submissions WHERE quiz_id = q.id)              AS attempt_count
            FROM `goniquizz_quizzes` q
            ORDER BY q.created_at DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    public function quizzes(): array
    {
        return $this->qb->table('goniquizz_quizzes')->orderBy('created_at', 'DESC')->get();
    }

    public function quiz(int $id): ?array
    {
        return $this->qb->table('goniquizz_quizzes')->where('id', '=', $id)->first() ?: null;
    }

    public function quizBySlug(string $slug): ?array
    {
        return $this->qb->table('goniquizz_quizzes')->where('slug', '=', $slug)->first() ?: null;
    }

    public function saveQuiz(array $data, ?int $id = null): int
    {
        if ($id) {
            $this->qb->table('goniquizz_quizzes')->where('id', '=', $id)->update($data);
            return $id;
        }
        $this->qb->table('goniquizz_quizzes')->insert($data);
        return (int) $this->conn->pdo()->lastInsertId();
    }

    public function deleteQuiz(int $id): void
    {
        $this->qb->table('goniquizz_quizzes')->where('id', '=', $id)->delete();
    }

    // ── Question CRUD ──────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> enriched with option_count */
    public function enrichedQuestions(int $quizId): array
    {
        $stmt = $this->conn->pdo()->prepare("
            SELECT q.*, COUNT(o.id) AS option_count
            FROM `goniquizz_questions` q
            LEFT JOIN `goniquizz_options` o ON o.question_id = q.id
            WHERE q.quiz_id = ?
            GROUP BY q.id
            ORDER BY q.sort_order ASC, q.id ASC
        ");
        $stmt->execute([$quizId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function questions(int $quizId): array
    {
        return $this->qb->table('goniquizz_questions')
            ->where('quiz_id', '=', $quizId)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    public function question(int $id): ?array
    {
        return $this->qb->table('goniquizz_questions')->where('id', '=', $id)->first() ?: null;
    }

    public function saveQuestion(array $data, ?int $id = null): int
    {
        if ($id) {
            $this->qb->table('goniquizz_questions')->where('id', '=', $id)->update($data);
            return $id;
        }
        $this->qb->table('goniquizz_questions')->insert($data);
        return (int) $this->conn->pdo()->lastInsertId();
    }

    public function deleteQuestion(int $id): void
    {
        // Options cascade via FK; delete question
        $this->qb->table('goniquizz_questions')->where('id', '=', $id)->delete();
    }

    // ── Options CRUD ───────────────────────────────────────────────────────────

    public function options(int $questionId): array
    {
        return $this->qb->table('goniquizz_options')
            ->where('question_id', '=', $questionId)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /** Replaces all options for the question (delete + re-insert). */
    public function saveOptions(int $questionId, array $rawOptions): void
    {
        $this->qb->table('goniquizz_options')->where('question_id', '=', $questionId)->delete();
        $i = 0;
        foreach ($rawOptions as $opt) {
            $text = trim((string)($opt['text'] ?? ''));
            if ($text === '') continue;
            $this->qb->table('goniquizz_options')->insert([
                'question_id' => $questionId,
                'option_text' => $text,
                'is_correct'  => !empty($opt['is_correct']) ? 1 : 0,
                'sort_order'  => $i++,
            ]);
        }
    }

    // ── Submission ─────────────────────────────────────────────────────────────

    /**
     * Score the quiz, persist submission + answers, return submission ID.
     *
     * @param array<int, list<int>> $answers  question_id → [option_ids]
     */
    public function submit(int $quizId, array $answers): int
    {
        $quiz      = $this->quiz($quizId);
        $questions = $this->questions($quizId);
        $total     = count($questions);
        $score     = null;
        $scorePct  = null;

        if ($quiz && $quiz['type'] === 'graded') {
            $score = 0;
            foreach ($questions as $q) {
                $qid        = (int)$q['id'];
                $correctIds = array_map(
                    'intval',
                    array_column(array_filter($this->options($qid), fn($o) => (int)$o['is_correct'] === 1), 'id')
                );
                $userIds = array_map('intval', $answers[$qid] ?? []);
                sort($correctIds); sort($userIds);
                if ($correctIds === $userIds) $score++;
            }
            $scorePct = $total > 0 ? (int) round($score / $total * 100) : 0;
        }

        $this->qb->table('goniquizz_submissions')->insert([
            'quiz_id'   => $quizId,
            'score'     => $score,
            'total'     => $total,
            'score_pct' => $scorePct,
        ]);
        $submissionId = (int) $this->conn->pdo()->lastInsertId();

        foreach ($answers as $qid => $optIds) {
            foreach ((array) $optIds as $oid) {
                if ((int)$oid <= 0) continue;
                $this->qb->table('goniquizz_submission_answers')->insert([
                    'submission_id' => $submissionId,
                    'question_id'   => (int)$qid,
                    'option_id'     => (int)$oid,
                ]);
            }
        }

        return $submissionId;
    }

    public function submission(int $id): ?array
    {
        return $this->qb->table('goniquizz_submissions')->where('id', '=', $id)->first() ?: null;
    }

    /**
     * @return array<int, list<int>>  question_id → [chosen option_ids]
     */
    public function submissionAnswers(int $submissionId): array
    {
        $rows = $this->qb->table('goniquizz_submission_answers')
            ->where('submission_id', '=', $submissionId)
            ->get();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['question_id']][] = (int)$row['option_id'];
        }
        return $map;
    }

    // ── Stats ──────────────────────────────────────────────────────────────────

    public function overallStats(): array
    {
        $row = $this->conn->pdo()->query("
            SELECT
                (SELECT COUNT(*) FROM goniquizz_quizzes)                    AS total_quizzes,
                (SELECT COUNT(*) FROM goniquizz_quizzes WHERE active = 1)   AS active_quizzes,
                (SELECT COUNT(*) FROM goniquizz_questions WHERE active = 1) AS total_questions,
                (SELECT COUNT(*) FROM goniquizz_submissions)                AS total_attempts
        ")->fetch(\PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function quizStats(int $quizId): array
    {
        $stmt = $this->conn->pdo()->prepare("
            SELECT COUNT(*) AS attempts,
                   ROUND(AVG(score_pct), 1) AS avg_pct,
                   MAX(score_pct) AS max_pct,
                   MIN(score_pct) AS min_pct
            FROM `goniquizz_submissions` WHERE quiz_id = ?
        ");
        $stmt->execute([$quizId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    public function recentSubmissions(int $quizId, int $limit = 30): array
    {
        return $this->qb->table('goniquizz_submissions')
            ->where('quiz_id', '=', $quizId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Poll results: option selection counts per question.
     * @return array<int, list<array<string,mixed>>>  question_id → options with `selections`
     */
    public function pollResults(int $quizId): array
    {
        $stmt = $this->conn->pdo()->prepare("
            SELECT o.question_id, o.id AS option_id, o.option_text,
                   COUNT(sa.id) AS selections
            FROM `goniquizz_options` o
            JOIN `goniquizz_questions` q ON q.id = o.question_id
            LEFT JOIN `goniquizz_submission_answers` sa ON sa.option_id = o.id
            WHERE q.quiz_id = ?
            GROUP BY o.id
            ORDER BY o.question_id ASC, o.sort_order ASC
        ");
        $stmt->execute([$quizId]);
        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[(int)$row['question_id']][] = $row;
        }
        return $result;
    }

    public function clearResults(int $quizId): void
    {
        // submission_answers cascade on submission delete
        $this->qb->table('goniquizz_submissions')->where('quiz_id', '=', $quizId)->delete();
    }

    // ── Helper link (for the goniquizz() global function) ──────────────────────

    public function quizLink(string $slug, string $label): string
    {
        $quiz = $this->quizBySlug($slug);
        if (!$quiz || !(int)$quiz['active']) return '';
        $url = self::$basePath . '/goniquizz/play?slug=' . urlencode($slug);
        $h   = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<a href="' . $h($url) . '" class="goniquizz-link" '
             . 'style="display:inline-flex;align-items:center;gap:6px;'
             . 'background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;'
             . 'text-decoration:none;border-radius:10px;padding:10px 22px;'
             . 'font-weight:700;font-size:14px;font-family:inherit;transition:opacity .15s">'
             . $h($label) . '</a>';
    }
}
