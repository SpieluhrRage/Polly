<?php

namespace App\Http\Controllers\Poll;

use App\Application\Poll\ListPollsService;
use App\Http\Controllers\BaseController;

class ListPollsController extends BaseController
{
    private ListPollsService $service;

    public function __construct(ListPollsService $service)
    {
        $this->service = $service;
    }

    public function __invoke(): void
    {
        try {
            $polls = $this->service->listActive();
            $this->json($polls, 200);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Internal server error'], 500);
        }
    }
}
