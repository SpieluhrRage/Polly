<?php

namespace App\Http\Controllers\Poll;

use App\Application\Auth\AuthService;
use App\Application\Poll\CreatePollService;
use App\Http\Controllers\BaseController;

class CreatePollController extends BaseController
{
    private AuthService $auth;
    private CreatePollService $service;

    public function __construct(AuthService $auth, CreatePollService $service)
    {
        $this->auth = $auth;
        $this->service = $service;
    }

    public function __invoke(): void
    {
        // 1. Достаём токен
        $token = $this->extractBearerToken();

        if ($token === null) {
            $this->json(['error' => 'Authorization token is required'], 401);
            return;
        }

        $user = $this->auth->findUserByToken($token);
        if ($user === null) {
            $this->json(['error' => 'Invalid or expired token'], 401);
            return;
        }

        // 2. Читаем тело запроса
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            $this->json(['error' => 'Invalid JSON'], 400);
            return;
        }

        $title = trim((string)($data['title'] ?? ''));
        $description = isset($data['description']) ? (string)$data['description'] : null;
        $options = $data['options'] ?? null;

        if ($title === '' || !is_array($options)) {
            $this->json(['error' => 'Title and options are required'], 400);
            return;
        }

        try {
            $result = $this->service->createPoll($user, $title, $description, $options);

            $this->json($result, 201);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Internal server error'], 500);
        }
    }

    private function extractBearerToken(): ?string
    {
        $header = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if ($header === null && function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
