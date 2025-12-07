<?php

declare(strict_types=1);

use App\Domain\Entity\Poll;
use App\Domain\Entity\Option;
use App\Infrastructure\Persistence\PollRepository;
use App\Infrastructure\Persistence\OptionRepository;

trait PollFactoryTrait
{

    private function createPollWithTwoOptions(): array
    {
        $pollRepository   = new PollRepository($this->pdo);
        $optionRepository = new OptionRepository($this->pdo);

        $poll = Poll::createNew(
            'Test poll',
            'For voting tests',
            $this->testUser->getId()
        );

        $poll = $pollRepository->save($poll);

        $opt1 = Option::createNew($poll->getId(), 'Option A');
        $opt2 = Option::createNew($poll->getId(), 'Option B');

        $opt1 = $optionRepository->save($opt1);
        $opt2 = $optionRepository->save($opt2);

        return [$poll, [$opt1, $opt2], $pollRepository, $optionRepository];
    }
}
