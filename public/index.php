<?php


declare(strict_types=1);

use App\Application\Auth\RegisterUserService;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Router;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\UserRepository;

require __DIR__ . '/../vendor/autoload.php';

$dbConfig = require __DIR__ . '/../config/db.php';
$database = new Database($dbConfig);
$pdo = $database->getConnection();


$userRepository = new UserRepository($pdo);
$registerUserService = new RegisterUserService($userRepository);
$router = new Router();


$router->get('/api/health', function () {
    $controller = new HealthCheckController();
    $controller();
});


$router->post('/api/register', function () use ($registerUserService) {
    $controller = new RegisterController($registerUserService);
    $controller();
});

// TODO: позже /api/login, /api/polls, /api/polls/{id}/vote и т.д.

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
