<?php

function db(): PDO
{
    static $pdo;
    return $pdo ??= new PDO(config('db_dsn'), config('db_user'), config('db_pass'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

// Every query in the app goes through here: prepared statement, values bound, never concatenated.
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    foreach (array_values($params) as $i => $v) {
        $type = is_int($v) ? PDO::PARAM_INT : (is_null($v) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $st->bindValue($i + 1, $v, $type);
    }
    $st->execute();
    return $st;
}
