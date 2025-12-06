<?php

namespace App\Domain\Entity;

class User
{
    private ?int $id;
    private string $username;
    private string $passwordHash;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        ?int $id,
        string $username,
        string $passwordHash,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
    }

    public static function createNew(string $username, string $passwordHash): self
    {
        return new self(
            null,
            $username,
            $passwordHash,
            new \DateTimeImmutable()
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
