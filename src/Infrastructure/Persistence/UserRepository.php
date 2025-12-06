<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->mapRowToUser($row);
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->mapRowToUser($row);
    }

    public function save(User $user): User
    {
        if ($user->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, password_hash, created_at) VALUES (:username, :password_hash, :created_at)'
            );

            $stmt->execute([
                'username'      => $user->getUsername(),
                'password_hash' => $user->getPasswordHash(),
                'created_at'    => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);

            $user->setId((int)$this->pdo->lastInsertId());

            return $user;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE users SET username = :username, password_hash = :password_hash WHERE id = :id'
        );

        $stmt->execute([
            'id'            => $user->getId(),
            'username'      => $user->getUsername(),
            'password_hash' => $user->getPasswordHash(),
        ]);

        return $user;
    }

    private function mapRowToUser(array $row): User
    {
        return new User(
            (int)$row['id'],
            $row['username'],
            $row['password_hash'],
            new \DateTimeImmutable($row['created_at'])
        );
    }
}
