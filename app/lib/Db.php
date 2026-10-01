<?php
declare(strict_types=1);

namespace Got;

use PDO;
use PDOStatement;

/**
 * Small database wrapper around PDO.
 * Production uses MySQL/MariaDB (Hostinger); SQLite is supported for local
 * development and very small installs.
 */
final class Db
{
    private static ?Db $instance = null;

    private function __construct(public readonly PDO $pdo, public readonly string $driver)
    {
    }

    public static function get(): self
    {
        if (self::$instance === null) {
            self::$instance = self::connect((array) Config::get('db', []));
        }
        return self::$instance;
    }

    public static function setInstance(?self $db): void
    {
        self::$instance = $db;
    }

    /** Open a connection from a configuration array. */
    public static function connect(array $cfg): self
    {
        $driver = (string) ($cfg['driver'] ?? 'mysql');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ];

        if ($driver === 'sqlite') {
            $path = (string) ($cfg['path'] ?? '');
            if ($path !== '' && $path[0] !== '/' && !preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
                $path = APP_ROOT . '/' . $path;
            }
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            $pdo->exec('PRAGMA journal_mode = WAL');
            return new self($pdo, 'sqlite');
        }

        $host = (string) ($cfg['host'] ?? 'localhost');
        $port = (int) ($cfg['port'] ?? 3306);
        $name = (string) ($cfg['name'] ?? '');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, (string) ($cfg['user'] ?? ''), (string) ($cfg['pass'] ?? ''), $options);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("SET time_zone = '+00:00'");
        return new self($pdo, 'mysql');
    }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $i = 0;
        foreach ($params as $key => $value) {
            $name = is_int($key) ? ++$i : (str_starts_with((string) $key, ':') ? $key : ':' . $key);
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_INT,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($name, is_bool($value) ? (int) $value : $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function value(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        $this->run($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    /** Update rows; returns the number of rows matched by the statement. */
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $sets = implode(', ', array_map(static fn ($c) => "{$c} = ?", array_keys($data)));
        $stmt = $this->run("UPDATE {$table} SET {$sets} WHERE {$where}", array_merge(array_values($data), $params));
        return $stmt->rowCount();
    }

    public function transaction(callable $callback): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $callback($this);
        }
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function tableExists(string $table): bool
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$table} LIMIT 1");
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Build a CREATE TABLE statement (plus indexes) for the active driver.
     * $columns uses "{id}" for the auto-increment primary key.
     */
    public function createTable(string $table, array $columns, array $indexes = []): void
    {
        $id = $this->driver === 'sqlite'
            ? 'INTEGER PRIMARY KEY AUTOINCREMENT'
            : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $defs = array_map(static fn ($c) => str_replace('{id}', $id, $c), $columns);

        if ($this->driver === 'mysql') {
            foreach ($indexes as $name => $index) {
                $defs[] = ($index['unique'] ?? false ? 'UNIQUE KEY ' : 'KEY ') . $name . ' (' . implode(', ', $index['columns']) . ')';
            }
            $sql = "CREATE TABLE IF NOT EXISTS {$table} (\n  " . implode(",\n  ", $defs)
                . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->pdo->exec($sql);
            return;
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$table} (\n  " . implode(",\n  ", $defs) . "\n)");
        foreach ($indexes as $name => $index) {
            $this->pdo->exec(sprintf(
                'CREATE %sINDEX IF NOT EXISTS %s ON %s (%s)',
                ($index['unique'] ?? false) ? 'UNIQUE ' : '',
                $name,
                $table,
                implode(', ', $index['columns'])
            ));
        }
    }
}
