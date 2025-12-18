<?php

declare(strict_types=1);

use App\Application\Vote\CastVoteService;
use App\Domain\Entity\Poll;
use App\Domain\Entity\Option;
use App\Domain\Entity\User;
use App\Infrastructure\Persistence\PollRepository;
use App\Infrastructure\Persistence\OptionRepository;

require_once __DIR__ . '/../../DatabaseTestCase.php';
require_once __DIR__ . '/PollFactoryTrait.php';

final class CastVoteServiceTest extends DatabaseTestCase
{
    use PollFactoryTrait;

    private CastVoteService $service;
    private PollRepository $pollRepository;
    private OptionRepository $optionRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pollRepository   = new PollRepository($this->pdo);
        $this->optionRepository = new OptionRepository($this->pdo);

        $this->service = new CastVoteService(
            $this->pollRepository,
            $this->optionRepository,
            $this->pdo
        );
    }

    public function testUserCanVoteOnceAndResultsAreAggregated(): void
    {
        [$poll, $options] = $this->createPollWithTwoOptions();

        $result = $this->service->castVote(
            $this->testUser,
            $poll->getId(),
            $options[0]->getId(),
            '127.0.0.1',
            'phpunit'
        );

        $this->assertSame($poll->getId(), $result['poll_id']);
        $this->assertSame($poll->getTitle(), $result['title']);

        $this->assertCount(2, $result['options']);

        // Первый вариант получил один голос, второй — ноль
        $this->assertSame($options[0]->getId(), $result['options'][0]['id']);
        $this->assertSame(1, $result['options'][0]['votes']);

        $this->assertSame($options[1]->getId(), $result['options'][1]['id']);
        $this->assertSame(0, $result['options'][1]['votes']);
    }

    public function testUserCannotVoteTwiceInSamePoll(): void
    {
        [$poll, $options] = $this->createPollWithTwoOptions();

        $this->service->castVote(
            $this->testUser,
            $poll->getId(),
            $options[0]->getId(),
            '127.0.0.1',
            'phpunit'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User has already voted in this poll');

        $this->service->castVote(
            $this->testUser,
            $poll->getId(),
            $options[1]->getId(),
            '127.0.0.1',
            'phpunit'
        );
    }

    public function testCannotVoteInClosedPoll(): void
    {
        [$poll, $options] = $this->createPollWithTwoOptions();

        // Закрываем опрос и сохраняем
        $poll->close();
        $this->pollRepository->save($poll);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Poll is closed');

        $this->service->castVote(
            $this->testUser,
            $poll->getId(),
            $options[0]->getId(),
            '127.0.0.1',
            'phpunit'
        );
    }

    public function testCannotVoteWithOptionFromAnotherPoll(): void
    {
        // Создаем два разных опроса
        [$poll1, $options1] = $this->createPollWithTwoOptions();
        [$poll2, $options2] = $this->createPollWithTwoOptions();

        $wrongOption = $options2[0];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Option not found for this poll');

        // Пытаемся проголосовать в poll1, но с option из poll2
        $this->service->castVote(
            $this->testUser,
            $poll1->getId(),
            $wrongOption->getId(),
            '127.0.0.1',
            'phpunit'
        );
    }

    public function testPollNotFoundThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Poll not found');

        $this->service->castVote(
            $this->testUser,
            999999,       
            1,
            '127.0.0.1',
            'phpunit'
        );
    }
}
