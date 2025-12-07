<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Persistence\Database;
use App\Domain\Entity\User;


abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Подтягиваем конфиг БД
        $config = require __DIR__ . '/../config/db.php';

        $db = new Database($config);
        $this->pdo = $db->getConnection();

        // Каждому тесту — своя транзакция
        $this->pdo->beginTransaction();

        // Создаем пользователя, который будет владельцем опросов и голосовать
        $hash = password_hash('secret-password', PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)'
        );
        $stmt->execute([
            'username'      => 'test_user_' . uniqid(),
            'password_hash' => $hash,
        ]);

        $userId = (int)$this->pdo->lastInsertId();

        $this->testUser = new User(
            $userId,
            'test_user',
            $hash,
            new \DateTimeImmutable()
        );
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }
}
