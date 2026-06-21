<?php
/**
 * WiseWallet 2.0 — PDO database gateway (singleton).
 *
 * Every query in the application goes through this class so we can guarantee:
 *   • prepared statements only (no string concatenation),
 *   • exceptions on error (ERRMODE_EXCEPTION),
 *   • real prepared statements (emulation disabled),
 *   • UTF-8 (utf8mb4) connections.
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    /** Return the shared PDO connection, creating it on first use. */
    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) ww_config('DB_HOST', '127.0.0.1');
        $port = (string) ww_config('DB_PORT', '3306');
        $name = (string) ww_config('DB_NAME', 'wisewallet');
        $user = (string) ww_config('DB_USER', 'root');
        $pass = (string) ww_config('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(503);
            if (WW_DEBUG) {
                exit('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES));
            }
            exit('Service temporarily unavailable. Please try again later.');
        }

        return self::$pdo;
    }

    /** Run a prepared SELECT and return all rows. */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Run a prepared SELECT and return the first row (or null). */
    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch a single scalar column from the first row. */
    public static function scalar(string $sql, array $params = [])
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return $val === false ? null : $val;
    }

    /** Run a prepared INSERT/UPDATE/DELETE; return affected row count. */
    public static function run(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT and return the new auto-increment id. */
    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function begin(): void  { self::pdo()->beginTransaction(); }
    public static function commit(): void { self::pdo()->commit(); }
    public static function rollback(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }
}
