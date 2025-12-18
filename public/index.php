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
use App\Application\Poll\ListPollsService;
use App\Http\Controllers\Poll\ListPollsController;
use App\Application\Poll\GetPollDetailsService;
use App\Http\Controllers\Poll\GetPollController;
use App\Application\Vote\CastVoteService;
use App\Http\Controllers\Poll\VoteController;
use App\Application\Vote\GetPollResultsService;
use App\Http\Controllers\Poll\GetPollResultsController;
use App\Application\Poll\ClosePollService;
use App\Http\Controllers\Poll\ClosePollController;
use App\Application\Poll\EditPollService;
use App\Http\Controllers\Poll\EditPollController;
use App\Application\Poll\OpenPollService;
use App\Http\Controllers\Poll\OpenPollController;
use App\Application\Poll\DeletePollService;
use App\Http\Controllers\Poll\DeletePollController;





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
$listPollsService    = new ListPollsService($pollRepository);
$getPollDetailsService = new GetPollDetailsService($pollRepository, $optionRepository);
$castVoteService       = new CastVoteService($pollRepository, $optionRepository, $pdo);
$getPollResultsService   = new GetPollResultsService($pollRepository, $pdo);
$closePollService      = new ClosePollService($pollRepository);
$editPollService       = new EditPollService($pollRepository);
$openPollService       = new OpenPollService($pollRepository);
$deletePollService     = new DeletePollService($pollRepository);

// Роутер
$router = new Router();


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


// СОЗДАНИЕ ОПРОСА (требует авторизации)
$router->post('/api/polls', function () use ($authService, $createPollService) {
    $controller = new CreatePollController($authService, $createPollService);
    $controller();
});

// СПИСОК АКТИВНЫХ ОПРОСОВ (публичный)
$router->get('/api/polls', function () use ($listPollsService) {
    $controller = new ListPollsController($listPollsService);
    $controller();
});

// ДЕТАЛИ ОПРОСА С ВАРИАНТАМИ (публичный)
$router->get('/api/polls/{id}', function ($id) use ($getPollDetailsService) {
    $controller = new GetPollController($getPollDetailsService);
    $controller((int)$id);
});

// ГОЛОСОВАНИЕ В ОПРОСЕ (требует авторизации)
$router->post('/api/polls/{id}/vote', function ($id) use ($authService, $castVoteService) {
    $controller = new VoteController($authService, $castVoteService);
    $controller((int)$id);
});

// РЕЗУЛЬТАТЫ ОПРОСА (публичный)
$router->get('/api/polls/{id}/results', function ($id) use ($getPollResultsService) {
    $controller = new GetPollResultsController($getPollResultsService);
    $controller((int)$id);
});

// ЗАКРЫТИЕ ОПРОСА (только создатель, требуется авторизация)
$router->post('/api/polls/{id}/close', function ($id) use ($authService, $closePollService) {
    $controller = new ClosePollController($authService, $closePollService);
    $controller((int)$id);
});

// РЕДАКТИРОВАНИЕ ОПРОСА (только создатель, только активный, требуется авторизация)
$router->post('/api/polls/{id}/edit', function ($id) use ($authService, $editPollService) {
    $controller = new EditPollController($authService, $editPollService);
    $controller((int)$id);
});

// ОТКРЫТИЕ ОПРОСА (только создатель, авторизация)
$router->post('/api/polls/{id}/open', function ($id) use ($authService, $openPollService) {
    $controller = new OpenPollController($authService, $openPollService);
    $controller((int)$id);
});

// УДАЛЕНИЕ ОПРОСА (только создатель, только закрытый, авторизация)
$router->post('/api/polls/{id}/delete', function ($id) use ($authService, $deletePollService) {
    $controller = new DeletePollController($authService, $deletePollService);
    $controller((int)$id);
});



$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
