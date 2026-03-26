<?php

declare(strict_types=1);

const DB_PATH = __DIR__ . '/../data/tracker.sqlite';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(dirname(DB_PATH))) {
        mkdir(dirname(DB_PATH), 0777, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS positions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            latitude REAL NOT NULL,
            longitude REAL NOT NULL,
            accuracy REAL NULL,
            source TEXT NOT NULL,
            captured_at TEXT NOT NULL,
            raw_payload TEXT NULL
        )'
    );

    return $pdo;
}
