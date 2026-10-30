<?php
// Connessione unica a PostgreSQL tramite PDO.

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $file = __DIR__ . '/config.php';
    if (!is_file($file)) {
        $file = __DIR__ . '/config.example.php';
    }
    $c = require $file;

    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $c['host'], $c['port'], $c['dbname']);
    $pdo = new PDO($dsn, $c['user'], $c['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

// Esegue una query parametrizzata e restituisce tutte le righe.
function query(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function query_riga(string $sql, array $params = []): ?array
{
    $righe = query($sql, $params);
    return $righe[0] ?? null;
}
