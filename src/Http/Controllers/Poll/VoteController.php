<?php

namespace App\Http\Controllers\Poll;

use App\Application\Auth\AuthService;
use App\Application\Vote\CastVoteService;
use App\Http\Controllers\BaseController;

class VoteController extends BaseController
{
    private AuthService $auth;
    private CastVoteService $service;

    public function __construct(AuthService $auth, CastVoteService $service)
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


        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            $this->json(['error' => 'Invalid JSON'], 400);
            return;
        }

        $optionId = isset($data['option_id']) ? (int)$data['option_id'] : 0;
        if ($optionId <= 0) {
            $this->json(['error' => 'option_id is required'], 400);
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $result = $this->service->castVote($user, $pollId, $optionId, $ip, $userAgent);
            $this->json($result, 200);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();

            if ($message === 'Poll not found') {
                $this->json(['error' => $message], 404);
            } elseif ($message === 'Option not found for this poll') {
                $this->json(['error' => $message], 400);
            } elseif ($message === 'User has already voted in this poll') {
                $this->json(['error' => $message], 409); // Conflict
            } 
            elseif ($message === 'Poll is closed') {
                $this->json(['error' => $message], 403);
            } // Forbidden
            else {
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
