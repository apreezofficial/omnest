<?php

declare(strict_types=1);

namespace Omnest\Services;

use Omnest\Auth\Passwords;
use Omnest\Exceptions\HttpException;
use Omnest\Exceptions\ValidationException;
use Omnest\Mail\Emails;
use Omnest\Mail\Mailer;
use Omnest\Repositories\ApiTokenRepository;
use Omnest\Repositories\UserRepository;
use Omnest\Repositories\UserTokenRepository;
use Omnest\Support\Config;
use Psr\Log\LoggerInterface;
use Throwable;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ApiTokenRepository $tokens,
        private readonly UserTokenRepository $links,
        private readonly Mailer $mailer,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return array{user: array<string, mixed>, token: string} */
    public function register(string $name, string $email, string $password, string $client): array
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new ValidationException(['email' => ['An account with this email already exists. Try signing in.']]);
        }

        $id = $this->users->create($name, $email, Passwords::hash($password));
        $user = $this->users->find($id);
        $this->sendVerification($user);

        return ['user' => $user, 'token' => $this->tokens->issue($id, $client)];
    }

    /** @return array{user: array<string, mixed>, token: string} */
    public function login(string $email, string $password, string $client): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            Passwords::dummyVerify($password);
            throw self::badCredentials();
        }
        if (!Passwords::verify($password, $user['password_hash'])) {
            throw self::badCredentials();
        }
        if (Passwords::needsRehash($user['password_hash'])) {
            $this->users->updatePassword((int) $user['id'], Passwords::hash($password));
        }

        return ['user' => $user, 'token' => $this->tokens->issue((int) $user['id'], $client)];
    }

    public function logout(int $tokenId): void
    {
        $this->tokens->revoke($tokenId);
    }

    /** @param array<string, mixed> $user */
    public function sendVerification(array $user): void
    {
        if ($user['email_verified_at'] !== null) {
            return;
        }
        $token = $this->links->issue((int) $user['id'], UserTokenRepository::VERIFY_EMAIL, '+24 hours');
        $url = $this->dashboardUrl('/verify-email', $token);
        $this->deliver(fn () => $this->mailer->send(Emails::verifyEmail($user['email'], $user['name'], $url)));
    }

    public function verifyEmail(string $token): void
    {
        $userId = $this->links->consume($token, UserTokenRepository::VERIFY_EMAIL);
        if ($userId === null) {
            throw new HttpException(422, 'link_invalid', 'This link has expired or was already used. Ask for a new one.');
        }
        $this->users->markEmailVerified($userId);
    }

    /** Always succeeds from the caller's view so it can't be used to discover accounts. */
    public function forgotPassword(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return;
        }
        $token = $this->links->issue((int) $user['id'], UserTokenRepository::RESET_PASSWORD, '+1 hour');
        $url = $this->dashboardUrl('/reset-password', $token);
        $this->deliver(fn () => $this->mailer->send(Emails::resetPassword($user['email'], $user['name'], $url)));
    }

    /** Sets the new password and signs out every session. */
    public function resetPassword(string $token, string $password): void
    {
        $userId = $this->links->consume($token, UserTokenRepository::RESET_PASSWORD);
        if ($userId === null) {
            throw new HttpException(422, 'link_invalid', 'This link has expired or was already used. Ask for a new one.');
        }
        $this->users->updatePassword($userId, Passwords::hash($password));
        $this->users->markEmailVerified($userId); // they proved they own the inbox
        $this->tokens->revokeAllForUser($userId);
    }

    private function dashboardUrl(string $path, string $token): string
    {
        return rtrim($this->config->string('dashboard_url'), '/') . $path . '?token=' . rawurlencode($token);
    }

    /** Email failures are logged, not shown: the account action itself succeeded. */
    private function deliver(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            $this->logger->error('Email delivery failed', ['exception' => $e]);
        }
    }

    private static function badCredentials(): HttpException
    {
        return new HttpException(401, 'invalid_credentials', 'That email and password don\'t match. Try again.');
    }
}
