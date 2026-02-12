<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public function __construct(private Database $database)
    {
    }

    public function login(string $username, string $password): bool
    {
        $stmt = $this->database->pdo()->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'service' => $user['service'],
            'role' => $user['role'],
        ];

        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }
}
