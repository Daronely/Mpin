<?php
/**
 * MPIN v1.1 migration — run once after updating the files.
 * Adds: users.locale, responses.fp_hash, audit_logs table.
 * Idempotent: safe to run multiple times. Self-deletes after success.
 */
declare(strict_types=1);
$cfg = require __DIR__ . '/app/config.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['db']['host'], (int)$cfg['db']['port'], $cfg['db']['name']),
    $cfg['db']['user'], $cfg['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$done = [];
$col = function (string $table, string $column) use ($pdo): bool {
    return (bool) $pdo->query(sprintf(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
        $table, $column))->fetchColumn();
};
if (!$col('users', 'locale')) {
    $pdo->exec("ALTER TABLE users ADD locale VARCHAR(5) NOT NULL DEFAULT 'en' AFTER role");
    $done[] = 'users.locale';
}
if (!$col('responses', 'fp_hash')) {
    $pdo->exec('ALTER TABLE responses ADD fp_hash CHAR(64) NULL AFTER guest_hash');
    $done[] = 'responses.fp_hash';
}
$hasAudit = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs'")->fetchColumn();
if (!$hasAudit) {
    $pdo->exec("CREATE TABLE audit_logs (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     INT UNSIGNED    NULL,
        actor       VARCHAR(30)     NOT NULL DEFAULT 'system',
        action      VARCHAR(60)     NOT NULL,
        target_type VARCHAR(20)     NOT NULL,
        target_id   VARCHAR(30)     NOT NULL DEFAULT '',
        details     VARCHAR(300)    NOT NULL DEFAULT '',
        created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_audit_created (created_at),
        KEY idx_audit_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $done[] = 'audit_logs';
}
@unlink(__FILE__);
header('Content-Type: text/plain; charset=utf-8');
echo 'MIGRATION_OK ' . ($done ? implode(', ', $done) : 'nothing to do') . PHP_EOL;
