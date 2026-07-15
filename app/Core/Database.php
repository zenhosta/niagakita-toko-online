<?php

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(?array $config = null, bool $withoutDatabase = false): PDO
    {
        if ($config === null && self::$pdo) return self::$pdo;
        $config ??= require BASE_PATH . '/config/database.php';
        if (!$config) throw new \RuntimeException('Konfigurasi database belum tersedia.');
        $db = $withoutDatabase ? '' : ';dbname=' . $config['database'];
        $dsn = "mysql:host={$config['host']};port={$config['port']}{$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if ($config === (require BASE_PATH . '/config/database.php') && !$withoutDatabase) self::$pdo = $pdo;
        return $pdo;
    }
}
