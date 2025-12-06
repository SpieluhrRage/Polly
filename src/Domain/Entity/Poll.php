<?php

namespace App\Domain\Entity;

class Poll
{
    private ?int $id;
    private string $title;
    private ?string $description;
    private bool $isActive;
    private int $createdBy;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        ?int $id,
        string $title,
        ?string $description,
        bool $isActive,
        int $createdBy,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->isActive = $isActive;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
    }

    public static function createNew(
        string $title,
        ?string $description,
        int $createdBy
    ): self {
        return new self(
            null,
            $title,
            $description,
            true,
            $createdBy,
            new \DateTimeImmutable()
        );
    }

    public function getId(): ?int          { return $this->id; }
    public function setId(int $id): void   { $this->id = $id; }

    public function getTitle(): string     { return $this->title; }
    public function getDescription(): ?string { return $this->description; }

    public function isActive(): bool       { return $this->isActive; }
    public function getCreatedBy(): int    { return $this->createdBy; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function close(): void { $this->isActive = false;}
}
