<?php

namespace App\Http\Controllers\Poll;

use App\Application\Vote\GetPollResultsService;
use App\Http\Controllers\BaseController;

class GetPollResultsController extends BaseController
{
    private GetPollResultsService $service;

    public function __construct(GetPollResultsService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $pollId): void
    {
        try {
            $results = $this->service->getResults($pollId);
            $this->json($results, 200);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Poll not found') {
                $this->json(['error' => $e->getMessage()], 404);
            } else {
                $this->json(['error' => $e->getMessage()], 400);
            }
        } catch (\Throwable $e) {
            $this->json(['error' => 'Internal server error'], 500);
        }
    }
}
