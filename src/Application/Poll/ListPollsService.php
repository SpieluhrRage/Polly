<?php

namespace App\Application\Poll;

use App\Domain\Repository\PollRepositoryInterface;

class ListPollsService
{
    private PollRepositoryInterface $polls;

    public function __construct(PollRepositoryInterface $polls)
    {
        $this->polls = $polls;
    }


    public function listActive(): array
    {
        $polls = $this->polls->findAllActive();

        $result = [];

        foreach ($polls as $poll) {
            $result[] = [
                'id'          => $poll->getId(),
                'title'       => $poll->getTitle(),
                'description' => $poll->getDescription(),
                'is_active'   => $poll->isActive(),
                'created_by'  => $poll->getCreatedBy(),
                'created_at'  => $poll->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $result;
    }
}
