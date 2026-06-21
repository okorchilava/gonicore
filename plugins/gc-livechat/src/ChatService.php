<?php

declare(strict_types=1);

namespace GCLiveChat;

use GoniCore\Core\Database\Connection;

/**
 * Conversation + message data access for GC Live Chat.
 * Visitor actions are authorised by the conversation's secret token; operator
 * actions go through the authenticated admin panel.
 */
final class ChatService
{
    public function __construct(private readonly Connection $db) {}

    // ── Conversations ───────────────────────────────────────────────────────────

    public function create(string $ip): array
    {
        $token = bin2hex(random_bytes(20)); // 40 chars
        $this->db->execute(
            'INSERT INTO `gc_chat_conversations` (`token`, `ip`) VALUES (?, ?)',
            [$token, $ip]
        );
        $id = (int) $this->db->pdo()->lastInsertId();
        return ['id' => $id, 'token' => $token, 'status' => 'ai'];
    }

    /** @return array<string,mixed>|null */
    public function byToken(string $token): ?array
    {
        if ($token === '') return null;
        return $this->db->queryOne('SELECT * FROM `gc_chat_conversations` WHERE `token` = ? LIMIT 1', [$token]);
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->queryOne('SELECT * FROM `gc_chat_conversations` WHERE `id` = ? LIMIT 1', [$id]);
    }

    public function setStatus(int $id, string $status, ?int $operatorId = null): void
    {
        if ($operatorId !== null) {
            $this->db->execute('UPDATE `gc_chat_conversations` SET `status` = ?, `operator_id` = ? WHERE `id` = ?', [$status, $operatorId, $id]);
        } else {
            $this->db->execute('UPDATE `gc_chat_conversations` SET `status` = ? WHERE `id` = ?', [$status, $id]);
        }
    }

    public function setVisitor(int $id, string $name, string $email): void
    {
        $this->db->execute(
            'UPDATE `gc_chat_conversations` SET `visitor_name` = ?, `visitor_email` = ? WHERE `id` = ?',
            [mb_substr($name, 0, 120), mb_substr($email, 0, 190), $id]
        );
    }

    public function setSummary(int $id, string $summary, string $topic): void
    {
        $this->db->execute(
            'UPDATE `gc_chat_conversations` SET `summary` = ?, `topic` = ? WHERE `id` = ? AND `summary` = \'\'',
            [mb_substr($summary, 0, 255), mb_substr($topic, 0, 60), $id]
        );
    }

    public function touch(int $id): void
    {
        $this->db->execute('UPDATE `gc_chat_conversations` SET `last_at` = CURRENT_TIMESTAMP WHERE `id` = ?', [$id]);
    }

    // ── Messages ────────────────────────────────────────────────────────────────

    public function addMessage(int $convId, string $sender, string $body, ?int $operatorId = null): int
    {
        $this->db->execute(
            'INSERT INTO `gc_chat_messages` (`conversation_id`, `sender`, `operator_id`, `body`, `seen_operator`)
             VALUES (?, ?, ?, ?, ?)',
            [$convId, $sender, $operatorId, $body, $sender === 'visitor' ? 0 : 1]
        );
        // Capture the insert id BEFORE the touch() UPDATE (an UPDATE resets it to 0).
        $id = (int) $this->db->pdo()->lastInsertId();
        $this->touch($convId);
        return $id;
    }

    /**
     * Messages in a conversation after $afterId (for polling).
     * @return list<array<string,mixed>>
     */
    public function messagesAfter(int $convId, int $afterId = 0): array
    {
        return $this->db->query(
            'SELECT `id`, `sender`, `operator_id`, `body`, `created_at`
               FROM `gc_chat_messages`
              WHERE `conversation_id` = ? AND `id` > ?
              ORDER BY `id` ASC',
            [$convId, $afterId]
        );
    }

    /**
     * Recent history (oldest first) for building AI context.
     * @return list<array<string,mixed>>
     */
    public function history(int $convId, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $rows = $this->db->query(
            "SELECT `sender`, `body` FROM `gc_chat_messages`
              WHERE `conversation_id` = ?
              ORDER BY `id` DESC LIMIT {$limit}",
            [$convId]
        );
        return array_reverse($rows);
    }

    // ── Operator inbox ──────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function inbox(int $limit = 80): array
    {
        $limit = max(1, min(200, $limit));
        return $this->db->query(
            "SELECT c.*,
                    (SELECT `body` FROM `gc_chat_messages` m WHERE m.`conversation_id` = c.`id` ORDER BY m.`id` DESC LIMIT 1) AS last_body,
                    (SELECT COUNT(*) FROM `gc_chat_messages` m WHERE m.`conversation_id` = c.`id` AND m.`sender` = 'visitor' AND m.`seen_operator` = 0) AS unread
               FROM `gc_chat_conversations` c
              WHERE c.`status` <> 'closed'
              ORDER BY (c.`status` = 'waiting') DESC, c.`last_at` DESC
              LIMIT {$limit}"
        );
    }

    public function waitingCount(): int
    {
        $r = $this->db->queryOne("SELECT COUNT(*) AS c FROM `gc_chat_conversations` WHERE `status` = 'waiting'");
        return (int) ($r['c'] ?? 0);
    }

    public function markSeenByOperator(int $convId): void
    {
        $this->db->execute('UPDATE `gc_chat_messages` SET `seen_operator` = 1 WHERE `conversation_id` = ? AND `sender` = \'visitor\'', [$convId]);
    }

    public function close(int $id): void
    {
        $this->setStatus($id, 'closed');
    }
}
