<?php

namespace App\Application\Auth;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use PDO;

class AuthService
{
    private UserRepositoryInterface $users;
    private PDO $pdo;

    public function __construct(UserRepositoryInterface $users, PDO $pdo)
    {
        $this->users = $users;
        $this->pdo = $pdo;
    }

    public function findUserByToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_id FROM auth_tokens WHERE token = :token'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $userId = (int)$row['user_id'];

        return $this->users->findById($userId);
    }
}
