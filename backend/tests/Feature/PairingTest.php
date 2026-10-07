<?php

declare(strict_types=1);

namespace Omnest\Tests\Feature;

use Omnest\Support\Clock;

final class PairingTest extends ApiTestCase
{
    /** @return array{token: string, child: int, code: string} */
    private function setUpCode(): array
    {
        $token = $this->registerParent();
        $child = $this->createChild($token);
        $res = $this->json('POST', "/api/v1/children/$child/pairing-codes", null, $token);
        self::assertSame(201, $res->status, $res->body);

        return ['token' => $token, 'child' => $child, 'code' => $res->decoded()['data']['code']];
    }

    /** @return array<string, mixed> */
    private function pair(string $code, string $ip = '10.0.0.9'): \Omnest\Http\Response
    {
        return $this->json('POST', '/api/v1/device/pair', [
            'code' => $code,
            'name' => "Tobi's phone",
            'model' => 'TECNO Spark 10',
            'os_version' => '13',
            'app_version' => '0.1.0',
        ], null, $ip);
    }

    public function testFullPairingFlow(): void
    {
        ['token' => $parent, 'child' => $child, 'code' => $code] = $this->setUpCode();
        self::assertMatchesRegularExpression('/^\d{6}$/', $code);

        $res = $this->pair($code);
        self::assertSame(201, $res->status, $res->body);
        $data = $res->decoded()['data'];
        self::assertStringStartsWith('omd_', $data['token']);
        self::assertSame($child, $data['child']['id']);
        self::assertSame('TECNO Spark 10', $data['device']['model']);

        // Device token works on device routes only; parent token doesn't work there and vice versa.
        $me = $this->json('GET', '/api/v1/device/me', null, $data['token']);
        self::assertSame(200, $me->status);
        self::assertSame('Tobi', $me->decoded()['data']['child']['name']);
        self::assertSame(401, $this->json('GET', '/api/v1/device/me', null, $parent)->status);
        self::assertSame(401, $this->json('GET', '/api/v1/children', null, $data['token'])->status);

        // Parent sees the device.
        $devices = $this->json('GET', "/api/v1/children/$child/devices", null, $parent)->decoded()['data'];
        self::assertCount(1, $devices);
        self::assertFalse($devices[0]['push_enabled']);
        self::assertSame(1, $this->json('GET', "/api/v1/children/$child", null, $parent)->decoded()['data']['device_count']);
    }

    public function testCodeIsSingleUse(): void
    {
        ['code' => $code] = $this->setUpCode();

        self::assertSame(201, $this->pair($code)->status);
        $again = $this->pair($code);
        self::assertSame(422, $again->status);
        self::assertArrayHasKey('code', $again->decoded()['error']['details']['fields']);
    }

    public function testCodeExpiresAfterTenMinutes(): void
    {
        Clock::freeze('2026-05-01 08:00:00');
        ['code' => $code] = $this->setUpCode();

        Clock::freeze('2026-05-01 08:10:01');
        self::assertSame(422, $this->pair($code)->status);
    }

    public function testNewCodeReplacesOldOne(): void
    {
        ['token' => $parent, 'child' => $child, 'code' => $old] = $this->setUpCode();
        $new = $this->json('POST', "/api/v1/children/$child/pairing-codes", null, $parent)->decoded()['data']['code'];

        if ($old !== $new) {
            self::assertSame(422, $this->pair($old)->status);
        }
        self::assertSame(201, $this->pair($new)->status);
    }

    public function testCodeWithSpacesIsAccepted(): void
    {
        ['code' => $code] = $this->setUpCode();

        self::assertSame(201, $this->pair(substr($code, 0, 3) . ' ' . substr($code, 3))->status);
    }

    public function testCodeIsStoredHashed(): void
    {
        ['code' => $code] = $this->setUpCode();

        $stored = (string) $this->db()->scalar('SELECT code_hash FROM pairing_codes');
        self::assertNotSame($code, $stored);
        self::assertSame(64, strlen($stored));
    }

    public function testGuessingIsThrottledPerIp(): void
    {
        $this->setUpCode();
        for ($i = 0; $i < 10; $i++) {
            $this->pair('000000', '10.9.9.9');
        }

        self::assertSame(429, $this->pair('000000', '10.9.9.9')->status);
        self::assertSame(422, $this->pair('000000', '10.9.9.10')->status);
    }

    public function testFcmTokenUpdate(): void
    {
        ['token' => $parent, 'child' => $child, 'code' => $code] = $this->setUpCode();
        $device = $this->pair($code)->decoded()['data']['token'];

        self::assertSame(204, $this->json('PUT', '/api/v1/device/fcm-token', ['fcm_token' => 'fcm-abc'], $device)->status);
        $devices = $this->json('GET', "/api/v1/children/$child/devices", null, $parent)->decoded()['data'];
        self::assertTrue($devices[0]['push_enabled']);
    }

    public function testParentUnpairRevokesDeviceToken(): void
    {
        ['token' => $parent, 'child' => $child, 'code' => $code] = $this->setUpCode();
        $paired = $this->pair($code)->decoded()['data'];

        $other = $this->registerParent('bayo@example.com');
        self::assertSame(404, $this->json('DELETE', "/api/v1/devices/{$paired['device']['id']}", null, $other)->status);

        self::assertSame(204, $this->json('DELETE', "/api/v1/devices/{$paired['device']['id']}", null, $parent)->status);
        self::assertSame(401, $this->json('GET', '/api/v1/device/me', null, $paired['token'])->status);
        self::assertSame([], $this->json('GET', "/api/v1/children/$child/devices", null, $parent)->decoded()['data']);
    }

    public function testDeletingChildRemovesDevices(): void
    {
        ['token' => $parent, 'child' => $child, 'code' => $code] = $this->setUpCode();
        $device = $this->pair($code)->decoded()['data']['token'];

        $this->json('DELETE', "/api/v1/children/$child", null, $parent);
        self::assertSame(401, $this->json('GET', '/api/v1/device/me', null, $device)->status);
    }
}
