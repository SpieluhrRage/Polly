<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Poll;
use App\Domain\Repository\PollRepositoryInterface;
use PDO;

class PollRepository implements PollRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Poll
    {
        $stmt = $this->pdo->prepare('SELECT * FROM polls WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->mapRowToPoll($row);
    }

    public function save(Poll $poll): Poll
    {
        if ($poll->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO polls (title, description, is_active, created_by, created_at)
                 VALUES (:title, :description, :is_active, :created_by, :created_at)'
            );

            $stmt->execute([
                'title'       => $poll->getTitle(),
                'description' => $poll->getDescription(),
                'is_active'   => $poll->isActive() ? 1 : 0,
                'created_by'  => $poll->getCreatedBy(),
                'created_at'  => $poll->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);

            $poll->setId((int)$this->pdo->lastInsertId());

            return $poll;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE polls
             SET title = :title,
                 description = :description,
                 is_active = :is_active
             WHERE id = :id'
        );

        $stmt->execute([
            'id'          => $poll->getId(),
            'title'       => $poll->getTitle(),
            'description' => $poll->getDescription(),
            'is_active'   => $poll->isActive() ? 1 : 0,
        ]);

        return $poll;
    }

    private function mapRowToPoll(array $row): Poll
    {
        return new Poll(
            (int)$row['id'],
            $row['title'],
            $row['description'],
            (bool)$row['is_active'],
            (int)$row['created_by'],
            new \DateTimeImmutable($row['created_at'])
        );
    }

    public function findAllActive(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM polls WHERE is_active = 1 ORDER BY created_at DESC'
        );
    
        $rows = $stmt->fetchAll();
        $result = [];
    
        foreach ($rows as $row) {
            $result[] = $this->mapRowToPoll($row);
        }
    
        return $result;
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM polls WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }


}
