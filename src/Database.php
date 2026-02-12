<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private PDO $pdo;

    public function __construct(string $dbPath)
    {
        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function migrate(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            email TEXT NOT NULL,
            service TEXT NOT NULL,
            role TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS solicitations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            external_id TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            requester_email TEXT,
            status TEXT NOT NULL,
            assigned_to INTEGER,
            payload_json TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (assigned_to) REFERENCES users (id)
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS workflow_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            solicitation_id INTEGER NOT NULL,
            from_status TEXT,
            to_status TEXT NOT NULL,
            note TEXT,
            actor_id INTEGER,
            assigned_to INTEGER,
            created_at TEXT NOT NULL,
            FOREIGN KEY (solicitation_id) REFERENCES solicitations (id),
            FOREIGN KEY (actor_id) REFERENCES users (id),
            FOREIGN KEY (assigned_to) REFERENCES users (id)
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            solicitation_id INTEGER NOT NULL,
            original_name TEXT NOT NULL,
            stored_name TEXT NOT NULL,
            mime_type TEXT,
            uploaded_by INTEGER,
            created_at TEXT NOT NULL,
            FOREIGN KEY (solicitation_id) REFERENCES solicitations (id),
            FOREIGN KEY (uploaded_by) REFERENCES users (id)
        )');

        $this->seedUsers();
    }

    private function seedUsers(): void
    {
        $users = [
            ['agent1', 'agent1', 'agent1@internal.local', 'Support', 'agent'],
            ['manager1', 'manager1', 'manager1@internal.local', 'Pilotage', 'manager'],
            ['legal1', 'legal1', 'legal1@internal.local', 'Juridique', 'agent'],
        ];

        $statement = $this->pdo->prepare('INSERT OR IGNORE INTO users (username, password_hash, email, service, role) VALUES (:username, :password_hash, :email, :service, :role)');

        foreach ($users as [$username, $password, $email, $service, $role]) {
            $statement->execute([
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':email' => $email,
                ':service' => $service,
                ':role' => $role,
            ]);
        }
    }
}
