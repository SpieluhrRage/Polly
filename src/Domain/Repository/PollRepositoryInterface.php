<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Poll;

interface PollRepositoryInterface
{
    public function findById(int $id): ?Poll;

    public function save(Poll $poll): Poll;
}
