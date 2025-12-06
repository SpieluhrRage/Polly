<?php

namespace App\Domain\Entity;

class Option
{
    private ?int $id;
    private int $pollId;
    private string $text;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        ?int $id,
        int $pollId,
        string $text,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->pollId = $pollId;
        $this->text = $text;
        $this->createdAt = $createdAt;
    }

    public static function createNew(int $pollId, string $text): self
    {
        return new self(
            null,
            $pollId,
            $text,
            new \DateTimeImmutable()
        );
    }

    public function getId(): ?int          { return $this->id; }
    public function setId(int $id): void   { $this->id = $id; }

    public function getPollId(): int       { return $this->pollId; }
    public function getText(): string      { return $this->text; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
