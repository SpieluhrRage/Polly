<?php

namespace App\Http\Controllers\Poll;

use App\Application\Poll\GetPollDetailsService;
use App\Http\Controllers\BaseController;

class GetPollController extends BaseController
{
    private GetPollDetailsService $service;

    public function __construct(GetPollDetailsService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): void
    {
        try {
            $poll = $this->service->getPoll($id);
            $this->json($poll, 200);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Internal server error'], 500);
        }
    }
}
