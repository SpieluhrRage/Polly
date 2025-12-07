<?php

namespace App\Application\Poll;

use App\Domain\Entity\User;
use App\Domain\Repository\PollRepositoryInterface;

class EditPollService
{
    private PollRepositoryInterface $polls;

    public function __construct(PollRepositoryInterface $polls)
    {
        $this->polls = $polls;
    }

    public function edit(User $user, int $pollId, ?string $title, ?string $description): array
    {
        $poll = $this->polls->findById($pollId);

        if ($poll === null) {
            throw new \RuntimeException('Poll not found');
        }

        if ($poll->getCreatedBy() !== $user->getId()) {
            throw new \RuntimeException('Forbidden: only creator can edit this poll');
        }

        if (!$poll->isActive()) {
            throw new \RuntimeException('Poll is closed and cannot be edited');
        }

        $hasChanges = false;

        if ($title !== null) {
            $title = trim($title);
            if ($title === '') {
                throw new \RuntimeException('Title cannot be empty');
            }
            $poll->setTitle($title);
            $hasChanges = true;
        }

        if ($description !== null) {
            $description = trim($description);
            if ($description === '') {
                $description = null;
            }
            $poll->setDescription($description);
            $hasChanges = true;
        }

        if (!$hasChanges) {
            throw new \RuntimeException('Nothing to update');
        }

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
