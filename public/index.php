<?php
// public/index.php

declare(strict_types=1);

use App\Http\Controllers\HealthCheckController;
use App\Http\Router;
use App\Infrastructure\Persistence\Database;

require __DIR__ . '/../vendor/autoload.php';

// Загружаем конфиг БД
$dbConfig = require __DIR__ . '/../config/db.php';

// Инициализируем соединение с БД (пока оно только создается — позже будем передавать в репозитории)
$database = new Database($dbConfig);

// Создаём роутер
$router = new Router();

// Простейший маршрут для проверки, что всё живо
$router->get('/api/health', function () {
    $controller = new HealthCheckController();
    $controller();
});

// Здесь позже добавим:
// - /api/register
// - /api/login
// - /api/polls и т.д.

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
