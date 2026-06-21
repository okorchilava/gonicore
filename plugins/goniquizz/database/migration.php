<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // ── Quizzes ──────────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `goniquizz_quizzes` (
                `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `title`        VARCHAR(255)  NOT NULL,
                `slug`         VARCHAR(120)  NOT NULL UNIQUE,
                `description`  TEXT          NOT NULL DEFAULT '',
                `type`         ENUM('graded','poll') NOT NULL DEFAULT 'graded'
                               COMMENT 'graded=has correct answers; poll=aggregate %',
                `show_results` TINYINT(1)    NOT NULL DEFAULT 1
                               COMMENT 'show score/results after completion',
                `allow_retake` TINYINT(1)    NOT NULL DEFAULT 0,
                `active`       TINYINT(1)    NOT NULL DEFAULT 1,
                `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Questions ─────────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `goniquizz_questions` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `quiz_id`    INT UNSIGNED NOT NULL,
                `question`   TEXT         NOT NULL,
                `type`       ENUM('single','multiple') NOT NULL DEFAULT 'single'
                             COMMENT 'single=radio, multiple=checkbox',
                `sort_order` SMALLINT     NOT NULL DEFAULT 0,
                `active`     TINYINT(1)   NOT NULL DEFAULT 1,
                INDEX `goniquizz_questions_quiz_idx` (`quiz_id`),
                CONSTRAINT `goniquizz_questions_quiz_fk`
                    FOREIGN KEY (`quiz_id`) REFERENCES `goniquizz_quizzes`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Options ───────────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `goniquizz_options` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `question_id` INT UNSIGNED NOT NULL,
                `option_text` TEXT         NOT NULL,
                `is_correct`  TINYINT(1)   NOT NULL DEFAULT 0,
                `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
                INDEX `goniquizz_options_question_idx` (`question_id`),
                CONSTRAINT `goniquizz_options_question_fk`
                    FOREIGN KEY (`question_id`) REFERENCES `goniquizz_questions`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Submissions ───────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `goniquizz_submissions` (
                `id`        INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `quiz_id`   INT UNSIGNED     NOT NULL,
                `score`     SMALLINT         NULL DEFAULT NULL
                            COMMENT 'correct answers count (graded only)',
                `total`     SMALLINT         NULL DEFAULT NULL,
                `score_pct` TINYINT UNSIGNED NULL DEFAULT NULL
                            COMMENT '0-100 (graded only)',
                `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `goniquizz_sub_quiz_idx` (`quiz_id`),
                CONSTRAINT `goniquizz_sub_quiz_fk`
                    FOREIGN KEY (`quiz_id`) REFERENCES `goniquizz_quizzes`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Submission answers ─────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `goniquizz_submission_answers` (
                `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `submission_id` INT UNSIGNED NOT NULL,
                `question_id`   INT UNSIGNED NOT NULL,
                `option_id`     INT UNSIGNED NOT NULL,
                INDEX `goniquizz_sa_sub_idx` (`submission_id`),
                CONSTRAINT `goniquizz_sa_sub_fk`
                    FOREIGN KEY (`submission_id`) REFERENCES `goniquizz_submissions`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $conn): void
    {
        foreach ([
            'goniquizz_submission_answers',
            'goniquizz_submissions',
            'goniquizz_options',
            'goniquizz_questions',
            'goniquizz_quizzes',
        ] as $table) {
            $conn->execute("DROP TABLE IF EXISTS `$table`");
        }
    }
};
