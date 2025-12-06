<?php

namespace App\Application\Auth;

use App\Domain\Repository\UserRepositoryInterface;
use PDO;

class LoginUserService
{
    private UserRepositoryInterface $users;
    private PDO $pdo;

    public function __construct(UserRepositoryInterface $users, PDO $pdo)
    {
        $this->users = $users;
        $this->pdo = $pdo;
    }

    public function login(string $username, string $plainPassword): array
    {
        $user = $this->users->findByUsername($username);

        if ($user === null) {
            throw new \RuntimeException('Неверный логин или пароль');
        }

        if (!password_verify($plainPassword, $user->getPasswordHash())) {
            throw new \RuntimeException('Неверный логин или пароль');
        }

        $token = bin2hex(random_bytes(32));

        $stmt = $this->pdo->prepare(
            'INSERT INTO auth_tokens (user_id, token, created_at)
             VALUES (:user_id, :token, NOW())'
        );

        $stmt->execute([
            'user_id' => $user->getId(),
            'token'   => $token,
        ]);

        return [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'token' => $token,
        ];
    }
}
