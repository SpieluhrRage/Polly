<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\LoginUserService;
use App\Http\Controllers\BaseController;

class LoginController extends BaseController
{
    private LoginUserService $service;

    public function __construct(LoginUserService $service)
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
            $result = $this->service->login($username, $password);

            $this->json([
                'user_id'  => $result['user_id'],
                'username' => $result['username'],
                'token'    => $result['token'],
            ], 200);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 401);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Internal server error'], 500);
        }
    }
}
