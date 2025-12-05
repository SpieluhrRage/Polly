<?php
// src/Http/Controllers/HealthCheckController.php

namespace App\Http\Controllers;

class HealthCheckController extends BaseController
{
    public function __invoke(): void
    {
        $this->json(['status' => 'ok', 'service' => 'game-balance-voting']);
    }
}
