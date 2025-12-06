<?php
declare(strict_types=1);

use App\Application\Auth\AuthService;
use App\Application\Auth\LoginUserService;
use App\Application\Auth\RegisterUserService;
use App\Application\Poll\CreatePollService;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Poll\CreatePollController;
use App\Http\Router;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\OptionRepository;
use App\Infrastructure\Persistence\PollRepository;
use App\Infrastructure\Persistence\UserRepository;

require __DIR__ . '/../vendor/autoload.php';

// БД
$dbConfig = require __DIR__ . '/../config/db.php';
$database = new Database($dbConfig);
$pdo = $database->getConnection();

// Репозитории
$userRepository   = new UserRepository($pdo);
$pollRepository   = new PollRepository($pdo);
$optionRepository = new OptionRepository($pdo);

// Сервисы
$registerUserService = new RegisterUserService($userRepository);
$loginUserService    = new LoginUserService($userRepository, $pdo);
$authService         = new AuthService($userRepository, $pdo);
$createPollService   = new CreatePollService($pollRepository, $optionRepository);

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

// Текущий пользователь
$router->get('/api/me', function () use ($authService) {
    $controller = new MeController($authService);
    $controller();
});

// СОЗДАНИЕ ОПРОСА (требует авторизации)
$router->post('/api/polls', function () use ($authService, $createPollService) {
    $controller = new CreatePollController($authService, $createPollService);
    $controller();
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
