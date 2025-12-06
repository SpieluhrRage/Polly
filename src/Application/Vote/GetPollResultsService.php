<?php

namespace App\Application\Vote;

use App\Domain\Repository\PollRepositoryInterface;
use PDO;

class GetPollResultsService
{
    private PollRepositoryInterface $polls;
    private PDO $pdo;

    public function __construct(PollRepositoryInterface $polls, PDO $pdo)
    {
        $this->polls = $polls;
        $this->pdo = $pdo;
    }

    public function getResults(int $pollId): array
    {
        $poll = $this->polls->findById($pollId);

        if ($poll === null) {
            throw new \RuntimeException('Poll not found');
        }

        $sql = '
            SELECT o.id, o.text, COUNT(v.id) AS votes
            FROM `options` o
            LEFT JOIN votes v
                ON v.option_id = o.id
                AND v.poll_id = :poll_id
            WHERE o.poll_id = :poll_id
            GROUP BY o.id, o.text
            ORDER BY o.id ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['poll_id' => $pollId]);
        $rows = $stmt->fetchAll();

        $options = [];
        foreach ($rows as $row) {
            $options[] = [
                'id'    => (int)$row['id'],
                'text'  => $row['text'],
                'votes' => (int)$row['votes'],
            ];
        }

        return [
            'poll_id' => $poll->getId(),
            'title'   => $poll->getTitle(),
            'options' => $options,
        ];
    }
}
