<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcpolicy_settings` (
                `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` LONGTEXT     NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo = $conn->pdo();
        $defaults = [
            'enabled'      => '1',
            'text'         => 'ვებსაიტი იყენებს "ქუქი ჩანაწერებს". დამატებითი ინფორმაციის მისაღებად იხილეთ:',
            'link_text'    => 'Cookie პოლიტიკა',
            'link_url'     => '#',
            'btn_text'     => 'კეთილი',
            'position'     => 'bottom',
            'show_decline' => '1',
            'decline_text' => 'უარყოფა',
            'expire_days'  => '365',
        ];
        foreach ($defaults as $k => $v) {
            $conn->execute(
                "INSERT IGNORE INTO `gcpolicy_settings` (`key`, `value`) VALUES ("
                . $pdo->quote($k) . ', ' . $pdo->quote($v) . ')'
            );
        }
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `gcpolicy_settings`");
    }
};
