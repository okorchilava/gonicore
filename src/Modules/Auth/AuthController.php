<?php

declare(strict_types=1);

namespace GoniCore\Modules\Auth;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Validation\Validator;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly Validator   $validator,
    ) {}

    /** POST /api/v1/auth/register */
    public function register(Request $request): Response
    {
        $data = $request->json();

        $this->validator->validate($data, [
            'name'     => 'required|string|min:2|max:100',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8|max:100',
        ]);

        $result = $this->auth->register($data);

        return Response::json($result, 201);
    }

    /** POST /api/v1/auth/login */
    public function login(Request $request): Response
    {
        $data = $request->json();

        $this->validator->validate($data, [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->auth->login(
            (string) $data['email'],
            (string) $data['password'],
        );

        return Response::json($result);
    }

    /** GET /api/v1/auth/me  — requires AuthMiddleware */
    public function me(Request $request): Response
    {
        $userId = (int) $request->getAttribute('userId');
        $user   = $this->auth->me($userId);

        return Response::json($user->toArray());
    }
}
