<?php

namespace App\Application\Poll;

use App\Domain\Entity\User;
use App\Domain\Repository\PollRepositoryInterface;

class OpenPollService
{
    private PollRepositoryInterface $polls;

    public function __construct(PollRepositoryInterface $polls)
    {
        $this->polls = $polls;
    }

    public function open(User $user, int $pollId): array
    {
        $poll = $this->polls->findById($pollId);

        if ($poll === null) {
            throw new \RuntimeException('Poll not found');
        }

        if ($poll->getCreatedBy() !== $user->getId()) {
            throw new \RuntimeException('Forbidden: only creator can open this poll');
        }

        if ($poll->isActive()) {
            return [
                'id'          => $poll->getId(),
                'title'       => $poll->getTitle(),
                'description' => $poll->getDescription(),
                'is_active'   => $poll->isActive(),
                'created_by'  => $poll->getCreatedBy(),
                'created_at'  => $poll->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        $poll->open();
        $poll = $this->polls->save($poll);

        return [
            'id'          => $poll->getId(),
            'title'       => $poll->getTitle(),
            'description' => $poll->getDescription(),
            'is_active'   => $poll->isActive(),
            'created_by'  => $poll->getCreatedBy(),
            'created_at'  => $poll->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
