<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Option;
use App\Domain\Repository\OptionRepositoryInterface;
use PDO;

class OptionRepository implements OptionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Option $option): Option
    {
        if ($option->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `options` (poll_id, `text`, created_at)
                 VALUES (:poll_id, :text, :created_at)'
            );

            $stmt->execute([
                'poll_id'    => $option->getPollId(),
                'text'       => $option->getText(),
                'created_at' => $option->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);

            $option->setId((int)$this->pdo->lastInsertId());

            return $option;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `options`
             SET `text` = :text
             WHERE id = :id'
        );

        $stmt->execute([
            'id'   => $option->getId(),
            'text' => $option->getText(),
        ]);

        return $option;
    }

    public function findByPollId(int $pollId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `options` WHERE poll_id = :poll_id ORDER BY id ASC'
        );
        $stmt->execute(['poll_id' => $pollId]);

        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = new Option(
                (int)$row['id'],
                (int)$row['poll_id'],
                $row['text'],
                new \DateTimeImmutable($row['created_at'])
            );
        }

        return $result;
    }
}
