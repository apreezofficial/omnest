<?php

declare(strict_types=1);

namespace Omnest\Controllers;

use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Repositories\UserRepository;
use Omnest\Services\AuthService;
use Omnest\Support\Validator;

final class AuthController
{
    private const PASSWORD = 'required|raw|string|min:8|max:200';

    public function __construct(private readonly AuthService $auth)
    {
    }

    public function register(Request $request): Response
    {
        $in = Validator::validate($request->all(), [
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:191',
            'password' => self::PASSWORD,
            'client' => 'nullable|string|max:40',
        ]);

        $result = $this->auth->register($in['name'], $in['email'], $in['password'], $in['client'] ?? 'web');

        return Response::created([
            'user' => UserRepository::present($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function login(Request $request): Response
    {
        $in = Validator::validate($request->all(), [
            'email' => 'required|email|max:191',
            'password' => 'required|raw|string|max:200',
            'client' => 'nullable|string|max:40',
        ]);

        $result = $this->auth->login($in['email'], $in['password'], $in['client'] ?? 'web');

        return Response::success([
            'user' => UserRepository::present($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout((int) $request->attribute('user')['token_id']);

        return Response::noContent();
    }

    public function me(Request $request): Response
    {
        return Response::success(UserRepository::present($request->attribute('user')));
    }

    public function verifyEmail(Request $request): Response
    {
        $in = Validator::validate($request->all(), ['token' => 'required|string|max:100']);
        $this->auth->verifyEmail($in['token']);

        return Response::success(['verified' => true]);
    }

    public function resendVerification(Request $request): Response
    {
        $this->auth->sendVerification($request->attribute('user'));

        return Response::success(['sent' => true], 202);
    }

    public function forgotPassword(Request $request): Response
    {
        $in = Validator::validate($request->all(), ['email' => 'required|email|max:191']);
        $this->auth->forgotPassword($in['email']);

        return Response::success(['sent' => true], 202);
    }

    public function resetPassword(Request $request): Response
    {
        $in = Validator::validate($request->all(), [
            'token' => 'required|string|max:100',
            'password' => self::PASSWORD,
        ]);
        $this->auth->resetPassword($in['token'], $in['password']);

        return Response::success(['reset' => true]);
    }
}
