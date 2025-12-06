<?php

namespace App\Application\Poll;

use App\Domain\Entity\Option;
use App\Domain\Entity\Poll;
use App\Domain\Entity\User;
use App\Domain\Repository\OptionRepositoryInterface;
use App\Domain\Repository\PollRepositoryInterface;

class CreatePollService
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
     * @param User   $creator
     * @param string $title
     * @param string|null $description
     * @param string[] $optionTexts
     *
     * @return array Структура, удобная для JSON-ответа
     *
     * @throws \RuntimeException при ошибках в данных
     */
    public function createPoll(
        User $creator,
        string $title,
        ?string $description,
        array $optionTexts
    ): array {
        $title = trim($title);
        $description = $description !== null ? trim($description) : null;

        if ($title === '') {
            throw new \RuntimeException('Title is required');
        }

        // Чистим и фильтруем варианты
        $cleanOptions = [];
        foreach ($optionTexts as $opt) {
            $opt = trim((string)$opt);
            if ($opt !== '') {
                $cleanOptions[] = $opt;
            }
        }

        if (count($cleanOptions) < 2) {
            throw new \RuntimeException('At least two options are required');
        }

        $poll = Poll::createNew($title, $description, $creator->getId());
        $poll = $this->polls->save($poll);

        $optionDtos = [];

        foreach ($cleanOptions as $optText) {
            $option = Option::createNew($poll->getId(), $optText);
            $option = $this->options->save($option);

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
