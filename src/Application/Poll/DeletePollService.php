<?php

namespace App\Application\Poll;

use App\Domain\Entity\User;
use App\Domain\Repository\PollRepositoryInterface;

class DeletePollService
{
    private PollRepositoryInterface $polls;

    public function __construct(PollRepositoryInterface $polls)
    {
        $this->polls = $polls;
    }

    public function delete(User $user, int $pollId): array
    {
        $poll = $this->polls->findById($pollId);

        if ($poll === null) {
            throw new \RuntimeException('Poll not found');
        }

        if ($poll->getCreatedBy() !== $user->getId()) {
            throw new \RuntimeException('Forbidden: only creator can delete this poll');
        }

        if ($poll->isActive()) {
            throw new \RuntimeException('Active poll cannot be deleted. Close it first.');
        }

        $this->polls->deleteById($pollId);

        return [
            'id'    => $pollId,
            'status'=> 'deleted',
        ];
    }
}
