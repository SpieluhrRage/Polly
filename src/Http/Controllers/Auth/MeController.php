<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\AuthService;
use App\Http\Controllers\BaseController;

class MeController extends BaseController
{
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function __invoke(): void
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

        $this->json([
            'id'         => $user->getId(),
            'username'   => $user->getUsername(),
            'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function extractBearerToken(): ?string
    {
        $header = null;
        
        // 1) Пробуем взять из $_SERVER (иногда так работает)
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
    
        // 2) Если всё ещё пусто — пробуем пройтись по всем заголовкам
        if ($header === null) {
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            
                foreach ($headers as $name => $value) {
                    if (strcasecmp($name, 'Authorization') === 0) {
                        $header = $value;
                        break;
                    }
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
