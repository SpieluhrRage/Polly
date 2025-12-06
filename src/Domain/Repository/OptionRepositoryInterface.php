<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Option;

interface OptionRepositoryInterface
{
  
    public function save(Option $option): Option;

    public function findByPollId(int $pollId): array;
}
