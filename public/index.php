<?php
declare(strict_types=1);

use App\Application\Auth\RegisterUserService;
use App\Application\Auth\LoginUserService;
use App\Application\Auth\AuthService;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Router;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\UserRepository;

require __DIR__ . '/../vendor/autoload.php';

// БД
$dbConfig = require __DIR__ . '/../config/db.php';
$database = new Database($dbConfig);
$pdo = $database->getConnection();

// Репозитории
$userRepository = new UserRepository($pdo);

// Сервисы
$registerUserService = new RegisterUserService($userRepository);
$loginUserService    = new LoginUserService($userRepository, $pdo);
$authService         = new AuthService($userRepository, $pdo);

// Роутер
$router = new Router();

// Health
$router->get('/api/health', function () {
    $controller = new HealthCheckController();
    $controller();
});

// Регистрация
$router->post('/api/register', function () use ($registerUserService) {
    $controller = new RegisterController($registerUserService);
    $controller();
});

// Логин
$router->post('/api/login', function () use ($loginUserService) {
    $controller = new LoginController($loginUserService);
    $controller();
});

// Текущий пользователь (требует токен)
$router->get('/api/me', function () use ($authService) {
    $controller = new MeController($authService);
    $controller();
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
