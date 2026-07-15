<?php

namespace App\Services;

final class LoginThrottle
{
    private function ensureTable(): void
    {
        db()->exec("CREATE TABLE IF NOT EXISTS login_attempts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, email_hash CHAR(64) NOT NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_login_attempt(ip_address,email_hash), INDEX idx_login_locked(locked_until)) ENGINE=InnoDB");
    }

    private function identity(string $email): array
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
        return [$ip, hash('sha256', strtolower(trim($email)))];
    }

    public function blocked(string $email): bool
    {
        $this->ensureTable();
        [$ip, $hash] = $this->identity($email);
        $stmt = db()->prepare('SELECT locked_until FROM login_attempts WHERE ip_address=? AND email_hash=?');
        $stmt->execute([$ip, $hash]);
        return ($until = $stmt->fetchColumn()) && strtotime((string) $until) > time();
    }

    public function failed(string $email): void
    {
        $this->ensureTable();
        [$ip, $hash] = $this->identity($email);
        db()->prepare("INSERT INTO login_attempts(ip_address,email_hash,attempts,locked_until) VALUES (?,?,1,NULL) ON DUPLICATE KEY UPDATE attempts=attempts+1,locked_until=IF(attempts+1>=5,DATE_ADD(NOW(),INTERVAL 15 MINUTE),NULL)")->execute([$ip, $hash]);
    }

    public function clear(string $email): void
    {
        $this->ensureTable();
        [$ip, $hash] = $this->identity($email);
        db()->prepare('DELETE FROM login_attempts WHERE ip_address=? AND email_hash=?')->execute([$ip, $hash]);
    }
}
