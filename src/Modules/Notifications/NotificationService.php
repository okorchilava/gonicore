<?php

declare(strict_types=1);

namespace GoniCore\Modules\Notifications;

/**
 * High-level notification factory.
 * Wraps NotificationRepository with named helpers for each event type.
 */
final class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $repo,
    ) {}

    // ── Factories ─────────────────────────────────────────────────────────────

    public function postCreated(string $title, int $authorId): void
    {
        $this->repo->create(
            type:    'post.created',
            title:   'New post created',
            message: '"' . $title . '" was created.',
            userId:  null,       // broadcast
            icon:    '✦',
            data:    ['post_title' => $title, 'author_id' => $authorId],
        );
    }

    public function postUpdated(string $title, int $editorId): void
    {
        $this->repo->create(
            type:    'post.updated',
            title:   'Post updated',
            message: '"' . $title . '" was edited.',
            userId:  null,
            icon:    '✎',
            data:    ['post_title' => $title, 'editor_id' => $editorId],
        );
    }

    public function postDeleted(string $title, int $editorId): void
    {
        $this->repo->create(
            type:    'post.deleted',
            title:   'Post deleted',
            message: '"' . $title . '" was permanently deleted.',
            userId:  null,
            icon:    '🗑',
            data:    ['post_title' => $title, 'editor_id' => $editorId],
        );
    }

    public function userRegistered(string $name, string $email): void
    {
        $this->repo->create(
            type:    'user.registered',
            title:   'New user registered',
            message: $name . ' (' . $email . ') joined.',
            userId:  null,
            icon:    '◉',
            data:    ['name' => $name, 'email' => $email],
        );
    }

    public function system(string $title, string $message, ?int $userId = null): void
    {
        $this->repo->create(
            type:    'system',
            title:   $title,
            message: $message,
            userId:  $userId,
            icon:    '⚙',
        );
    }

    /**
     * Admin → all-admins announcement. Shown in the topbar megaphone feed,
     * deliberately kept OUT of the regular notification bell.
     */
    public function broadcast(string $title, ?string $message = null): void
    {
        $this->repo->create(
            type:    'broadcast',
            title:   $title,
            message: $message,
            userId:  null,         // broadcast
            icon:    '📣',
        );
    }

    // ── Broadcast feed (megaphone) ────────────────────────────────────────────

    /** @return list<array<string, mixed>> */
    public function broadcasts(int $limit = 30): array
    {
        return $this->repo->broadcasts($limit);
    }

    public function broadcastUnreadCount(): int
    {
        return $this->repo->broadcastUnreadCount();
    }

    public function markBroadcastsRead(): void
    {
        $this->repo->markBroadcastsRead();
    }

    // ── Delegate read methods ─────────────────────────────────────────────────

    public function forUser(int $userId, int $limit = 30): array
    {
        return $this->repo->forUser($userId, $limit);
    }

    public function unreadCount(int $userId): int
    {
        return $this->repo->unreadCount($userId);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->repo->markRead($id, $userId);
    }

    public function markAllRead(int $userId): void
    {
        $this->repo->markAllRead($userId);
    }
}
