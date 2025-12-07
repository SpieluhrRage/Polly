<?php

namespace App\Http\Controllers\Poll;

use App\Application\Auth\AuthService;
use App\Application\Poll\OpenPollService;
use App\Http\Controllers\BaseController;

class OpenPollController extends BaseController
{
    private AuthService $auth;
    private OpenPollService $service;

    public function __construct(AuthService $auth, OpenPollService $service)
    {
        $this->auth = $auth;
        $this->service = $service;
    }

    public function __invoke(int $pollId): void
    {
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

        try {
            $pollData = $this->service->open($user, $pollId);
            $this->json($pollData, 200);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();

            if ($message === 'Poll not found') {
                $this->json(['error' => $message], 404);
            } elseif ($message === 'Forbidden: only creator can open this poll') {
                $this->json(['error' => $message], 403);
            } else {
                $this->json(['error' => $message], 400);
            }
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
