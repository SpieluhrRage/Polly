<?php

declare(strict_types=1);

use App\Application\Poll\CreatePollService;
use App\Domain\Entity\Option;
use App\Domain\Entity\Poll;
use App\Infrastructure\Persistence\PollRepository;
use App\Infrastructure\Persistence\OptionRepository;

require_once __DIR__ . '/../../DatabaseTestCase.php';

final class CreatePollServiceTest extends DatabaseTestCase
{
    private CreatePollService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $pollRepository   = new PollRepository($this->pdo);
        $optionRepository = new OptionRepository($this->pdo);

        $this->service = new CreatePollService(
            $pollRepository,
            $optionRepository
        );
    }

    public function testCreatePollSuccess(): void
    {
        $title       = 'Balance changes 1.0';
        $description = 'Test poll for game balance';
        $options     = ['Buff mages', 'Nerf warriors'];

        $result = $this->service->createPoll(
            $this->testUser,
            $title,
            $description,
            $options
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertIsInt($result['id']);

        $this->assertSame($title, $result['title']);
        $this->assertSame($description, $result['description']);
        $this->assertTrue($result['is_active']);
        $this->assertSame($this->testUser->getId(), $result['created_by']);

        $this->assertArrayHasKey('options', $result);
        $this->assertCount(2, $result['options']);

        foreach ($result['options'] as $opt) {
            $this->assertArrayHasKey('id', $opt);
            $this->assertArrayHasKey('text', $opt);
        }
    }

    public function testCreatePollRequiresTitle(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Title is required');

        $this->service->createPoll(
            $this->testUser,
            '   ',                    
            'Some description',
            ['A', 'B']
        );
    }

    public function testCreatePollRequiresAtLeastTwoOptions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('At least two options are required');

        $this->service->createPoll(
            $this->testUser,
            'Valid title',
            null,
            ['Only one option']      
        );
    }

    public function testCreatePollTrimsAndFiltersEmptyOptions(): void
    {
        $title   = 'Another poll';
        $options = ['  First  ', '   ', "\nSecond\n"];

        $result = $this->service->createPoll(
            $this->testUser,
            $title,
            null,
            $options
        );

        $this->assertCount(2, $result['options']);
        $this->assertSame('First', $result['options'][0]['text']);
        $this->assertSame('Second', $result['options'][1]['text']);
    }
}
