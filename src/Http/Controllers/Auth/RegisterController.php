<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\RegisterUserService;
use App\Http\Controllers\BaseController;

class RegisterController extends BaseController
{
    private RegisterUserService $service;

    public function __construct(RegisterUserService $service)
    {
        $this->service = $service;
    }

    public function __invoke(): void
    {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            $this->json(['error' => 'Invalid JSON'], 400);
            return;
        }

        $username = trim($data['username'] ?? '');
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->json(['error' => 'Username and password are required'], 400);
            return;
        }

        try {
            $user = $this->service->register($username, $password);

            $this->json([
                'id'       => $user->getId(),
                'username' => $user->getUsername(),
                'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], 201);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Internal server error'], 500);
        }
    }
}
