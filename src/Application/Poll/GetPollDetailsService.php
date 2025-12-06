<?php

namespace App\Application\Poll;

use App\Domain\Repository\PollRepositoryInterface;
use App\Domain\Repository\OptionRepositoryInterface;

class GetPollDetailsService
{
    private PollRepositoryInterface $polls;
    private OptionRepositoryInterface $options;

    public function __construct(
        PollRepositoryInterface $polls,
        OptionRepositoryInterface $options
    ) {
        $this->polls = $polls;
        $this->options = $options;
    }

    /**
     * @return array
     *
     * @throws \RuntimeException если опрос не найден
     */
    public function getPoll(int $id): array
    {
        $poll = $this->polls->findById($id);

        if ($poll === null) {
            throw new \RuntimeException('Poll not found');
        }

        $options = $this->options->findByPollId($poll->getId());

        $optionDtos = [];
        foreach ($options as $option) {
            $optionDtos[] = [
                'id'   => $option->getId(),
                'text' => $option->getText(),
            ];
        }

        return [
            'id'          => $poll->getId(),
            'title'       => $poll->getTitle(),
            'description' => $poll->getDescription(),
            'is_active'   => $poll->isActive(),
            'created_by'  => $poll->getCreatedBy(),
            'created_at'  => $poll->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'options'     => $optionDtos,
        ];
    }
}
