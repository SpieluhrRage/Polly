<?php

namespace App\Application\Vote;

use App\Domain\Entity\User;
use App\Domain\Repository\OptionRepositoryInterface;
use App\Domain\Repository\PollRepositoryInterface;
use PDO;
use PDOException;

class CastVoteService
{
    private PollRepositoryInterface $polls;
    private OptionRepositoryInterface $options;
    private PDO $pdo;

    public function __construct(
        PollRepositoryInterface $polls,
        OptionRepositoryInterface $options,
        PDO $pdo
    ) {
        $this->polls = $polls;
        $this->options = $options;
        $this->pdo = $pdo;
    }

    public function castVote(
        User $user,
        int $pollId,
        int $optionId,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        $poll = $this->polls->findById($pollId);

        if ($poll === null) {
            $this->logVote($pollId, $optionId, $user->getId(), $ipAddress, $userAgent, false, 'Poll not found');
            throw new \RuntimeException('Poll not found');
        }

        $option = $this->options->findByIdAndPollId($optionId, $pollId);

        if ($option === null) {
            $this->logVote($pollId, $optionId, $user->getId(), $ipAddress, $userAgent, false, 'Option not found for this poll');
            throw new \RuntimeException('Option not found for this poll');
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO votes (poll_id, option_id, user_id, created_at)
                 VALUES (:poll_id, :option_id, :user_id, NOW())'
            );

            $stmt->execute([
                'poll_id'   => $pollId,
                'option_id' => $optionId,
                'user_id'   => $user->getId(),
            ]);

            $this->logVote($pollId, $optionId, $user->getId(), $ipAddress, $userAgent, true, 'ok');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->logVote($pollId, $optionId, $user->getId(), $ipAddress, $userAgent, false, 'User has already voted');
                throw new \RuntimeException('User has already voted in this poll');
            }

            $this->logVote($pollId, $optionId, $user->getId(), $ipAddress, $userAgent, false, 'DB error');
            throw new \RuntimeException('Database error');
        }

        $results = $this->calculateResults($pollId);

        return [
            'poll_id' => $poll->getId(),
            'title'   => $poll->getTitle(),
            'options' => $results,
        ];
    }

    private function calculateResults(int $pollId): array
    {
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

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id'    => (int)$row['id'],
                'text'  => $row['text'],
                'votes' => (int)$row['votes'],
            ];
        }

        return $result;
    }

    private function logVote(
        ?int $pollId,
        ?int $optionId,
        ?int $userId,
        ?string $ipAddress,
        ?string $userAgent,
        bool $success,
        string $message
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vote_logs (poll_id, option_id, user_id, ip_address, user_agent, success, message, created_at)
             VALUES (:poll_id, :option_id, :user_id, :ip_address, :user_agent, :success, :message, NOW())'
        );

        $stmt->execute([
            'poll_id'    => $pollId,
            'option_id'  => $optionId,
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success'    => $success ? 1 : 0,
            'message'    => $message,
        ]);
    }
}
