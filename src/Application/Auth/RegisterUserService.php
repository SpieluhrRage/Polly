<?php

namespace App\Application\Auth;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class RegisterUserService
{
    private UserRepositoryInterface $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function register(string $username, string $plainPassword): User
    {
        $existing = $this->users->findByUsername($username);

        if ($existing !== null) {
            throw new \RuntimeException('Пользователь с таким логином уже существует');
        }

        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $user = User::createNew($username, $passwordHash);

        return $this->users->save($user);
    }
}
