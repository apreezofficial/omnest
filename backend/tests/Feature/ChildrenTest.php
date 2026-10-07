<?php

declare(strict_types=1);

namespace Omnest\Tests\Feature;

final class ChildrenTest extends ApiTestCase
{
    public function testCrud(): void
    {
        $token = $this->registerParent();

        $created = $this->json('POST', '/api/v1/children', [
            'name' => 'Tobi',
            'age_tier' => 'preteen',
            'birth_date' => '2015-04-02',
            'avatar' => 'fox',
        ], $token);
        self::assertSame(201, $created->status, $created->body);
        $child = $created->decoded()['data'];
        self::assertSame(['Tobi', 'preteen', '2015-04-02', 'fox', 0], [$child['name'], $child['age_tier'], $child['birth_date'], $child['avatar'], $child['device_count']]);

        $list = $this->json('GET', '/api/v1/children', null, $token)->decoded()['data'];
        self::assertCount(1, $list);

        $updated = $this->json('PATCH', "/api/v1/children/{$child['id']}", ['name' => 'Tobiloba', 'birth_date' => null], $token);
        self::assertSame(200, $updated->status, $updated->body);
        self::assertSame('Tobiloba', $updated->decoded()['data']['name']);
        self::assertNull($updated->decoded()['data']['birth_date']);
        self::assertSame('preteen', $updated->decoded()['data']['age_tier']);

        self::assertSame(204, $this->json('DELETE', "/api/v1/children/{$child['id']}", null, $token)->status);
        self::assertSame(404, $this->json('GET', "/api/v1/children/{$child['id']}", null, $token)->status);
    }

    public function testValidation(): void
    {
        $token = $this->registerParent();

        $res = $this->json('POST', '/api/v1/children', ['name' => '', 'age_tier' => 'baby', 'birth_date' => '2999-01-01'], $token);
        self::assertSame(422, $res->status);
        self::assertSame(['name', 'age_tier'], array_keys($res->decoded()['error']['details']['fields']));

        $future = $this->json('POST', '/api/v1/children', ['name' => 'Tobi', 'age_tier' => 'kid', 'birth_date' => '2999-01-01'], $token);
        self::assertSame(422, $future->status);
        self::assertArrayHasKey('birth_date', $future->decoded()['error']['details']['fields']);
    }

    public function testParentsOnlySeeTheirOwnChildren(): void
    {
        $ada = $this->registerParent('ada@example.com');
        $bayo = $this->registerParent('bayo@example.com');
        $childId = $this->createChild($ada);

        self::assertSame([], $this->json('GET', '/api/v1/children', null, $bayo)->decoded()['data']);
        self::assertSame(404, $this->json('GET', "/api/v1/children/$childId", null, $bayo)->status);
        self::assertSame(404, $this->json('PATCH', "/api/v1/children/$childId", ['name' => 'X'], $bayo)->status);
        self::assertSame(404, $this->json('DELETE', "/api/v1/children/$childId", null, $bayo)->status);
        self::assertSame(404, $this->json('POST', "/api/v1/children/$childId/pairing-codes", null, $bayo)->status);
        self::assertSame(200, $this->json('GET', "/api/v1/children/$childId", null, $ada)->status);
    }

    public function testRequiresParentToken(): void
    {
        self::assertSame(401, $this->json('GET', '/api/v1/children')->status);
    }
}
