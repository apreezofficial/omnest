<?php

declare(strict_types=1);

namespace Omnest\Tests\Feature;

final class AuthTest extends ApiTestCase
{
    public function testRegisterReturnsTokenAndSendsVerificationEmail(): void
    {
        $res = $this->json('POST', '/api/v1/auth/register', [
            'name' => 'Ada',
            'email' => '  Ada@Example.COM ',
            'password' => 'correct horse',
        ]);

        self::assertSame(201, $res->status);
        $data = $res->decoded()['data'];
        self::assertStringStartsWith('omp_', $data['token']);
        self::assertSame('ada@example.com', $data['user']['email']);
        self::assertFalse($data['user']['email_verified']);
        self::assertSame('ada@example.com', $this->mailer->last()->to);
        self::assertStringContainsString('http://dash.test/verify-email?token=', $this->mailer->last()->text);

        // Only the hash is stored.
        $stored = $this->db()->scalar('SELECT token_hash FROM api_tokens');
        self::assertSame(hash('sha256', $data['token']), $stored);
        self::assertStringStartsWith('$argon2id$', (string) $this->db()->scalar('SELECT password_hash FROM users'));
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $this->registerParent();
        $res = $this->json('POST', '/api/v1/auth/register', ['name' => 'Ada', 'email' => 'ADA@example.com', 'password' => 'another one']);

        self::assertSame(422, $res->status);
        self::assertArrayHasKey('email', $res->decoded()['error']['details']['fields']);
    }

    public function testShortPasswordIsRejected(): void
    {
        $res = $this->json('POST', '/api/v1/auth/register', ['name' => 'Ada', 'email' => 'a@b.co', 'password' => 'short']);

        self::assertSame(422, $res->status);
        self::assertSame(['Must be at least 8 characters.'], $res->decoded()['error']['details']['fields']['password']);
    }

    public function testLoginMeLogout(): void
    {
        $this->registerParent();

        $bad = $this->json('POST', '/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong password']);
        self::assertSame(401, $bad->status);
        self::assertSame('invalid_credentials', $bad->decoded()['error']['code']);

        $unknown = $this->json('POST', '/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'whatever1']);
        self::assertSame(401, $unknown->status);

        $token = $this->json('POST', '/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'correct horse'])->decoded()['data']['token'];

        $me = $this->json('GET', '/api/v1/auth/me', null, $token);
        self::assertSame(200, $me->status);
        self::assertSame('Ada', $me->decoded()['data']['name']);

        self::assertSame(204, $this->json('POST', '/api/v1/auth/logout', null, $token)->status);
        self::assertSame(401, $this->json('GET', '/api/v1/auth/me', null, $token)->status);
    }

    public function testMissingOrForeignTokenIs401(): void
    {
        self::assertSame(401, $this->json('GET', '/api/v1/auth/me')->status);
        self::assertSame(401, $this->json('GET', '/api/v1/auth/me', null, 'omp_' . str_repeat('0', 64))->status);
    }

    public function testExpiredTokenIs401(): void
    {
        \Omnest\Support\Clock::freeze('2026-01-01 00:00:00');
        $token = $this->registerParent();
        \Omnest\Support\Clock::freeze('2026-02-15 00:00:00');

        self::assertSame(401, $this->json('GET', '/api/v1/auth/me', null, $token)->status);
    }

    public function testVerifyEmailLinkWorksOnce(): void
    {
        $token = $this->registerParent();
        $link = $this->tokenFromLastEmail();

        self::assertSame(200, $this->json('POST', '/api/v1/auth/email/verify', ['token' => $link])->status);
        self::assertTrue($this->json('GET', '/api/v1/auth/me', null, $token)->decoded()['data']['email_verified']);

        $again = $this->json('POST', '/api/v1/auth/email/verify', ['token' => $link]);
        self::assertSame(422, $again->status);
        self::assertSame('link_invalid', $again->decoded()['error']['code']);
    }

    public function testResendInvalidatesOlderLink(): void
    {
        $token = $this->registerParent();
        $first = $this->tokenFromLastEmail();
        self::assertSame(202, $this->json('POST', '/api/v1/auth/email/resend', null, $token)->status);
        $second = $this->tokenFromLastEmail();

        self::assertNotSame($first, $second);
        self::assertSame(422, $this->json('POST', '/api/v1/auth/email/verify', ['token' => $first])->status);
        self::assertSame(200, $this->json('POST', '/api/v1/auth/email/verify', ['token' => $second])->status);
    }

    public function testPasswordResetChangesPasswordAndSignsOutEverywhere(): void
    {
        $oldToken = $this->registerParent();
        $sentBefore = count($this->mailer->sent);

        // Unknown email: same response, nothing sent.
        self::assertSame(202, $this->json('POST', '/api/v1/auth/password/forgot', ['email' => 'nobody@example.com'])->status);
        self::assertCount($sentBefore, $this->mailer->sent);

        self::assertSame(202, $this->json('POST', '/api/v1/auth/password/forgot', ['email' => 'ada@example.com'])->status);
        $link = $this->tokenFromLastEmail();
        self::assertStringContainsString('/reset-password?token=', $this->mailer->last()->text);

        $res = $this->json('POST', '/api/v1/auth/password/reset', ['token' => $link, 'password' => 'brand new pass']);
        self::assertSame(200, $res->status);

        self::assertSame(401, $this->json('GET', '/api/v1/auth/me', null, $oldToken)->status);
        self::assertSame(401, $this->json('POST', '/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'correct horse'])->status);
        self::assertSame(200, $this->json('POST', '/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'brand new pass'])->status);
        self::assertSame(422, $this->json('POST', '/api/v1/auth/password/reset', ['token' => $link, 'password' => 'again again'])->status);
    }

    public function testResetLinkExpiresAfterAnHour(): void
    {
        \Omnest\Support\Clock::freeze('2026-03-01 10:00:00');
        $this->registerParent();
        $this->json('POST', '/api/v1/auth/password/forgot', ['email' => 'ada@example.com']);
        $link = $this->tokenFromLastEmail();

        \Omnest\Support\Clock::freeze('2026-03-01 11:01:00');
        self::assertSame(422, $this->json('POST', '/api/v1/auth/password/reset', ['token' => $link, 'password' => 'brand new pass'])->status);
    }

    public function testLoginIsRateLimited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->json('POST', '/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'whatever1']);
        }
        $res = $this->json('POST', '/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'whatever1']);

        self::assertSame(429, $res->status);
        self::assertNotNull($res->header('Retry-After'));
    }
}
