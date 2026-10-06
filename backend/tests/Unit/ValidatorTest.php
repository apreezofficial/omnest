<?php

declare(strict_types=1);

namespace Omnest\Tests\Unit;

use Omnest\Exceptions\ValidationException;
use Omnest\Support\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testReturnsOnlyDeclaredTrimmedFields(): void
    {
        $clean = Validator::validate(
            ['name' => '  Tobi ', 'age_tier' => 'kid', 'is_admin' => true],
            ['name' => 'required|string|max:80', 'age_tier' => 'required|in:kid,preteen,teen'],
        );

        self::assertSame(['name' => 'Tobi', 'age_tier' => 'kid'], $clean);
    }

    public function testCollectsErrorsPerField(): void
    {
        try {
            Validator::validate(
                ['email' => 'not-an-email', 'minutes' => 500, 'birth_date' => '2020-02-30'],
                [
                    'email' => 'required|email',
                    'password' => 'required|string|min:8',
                    'minutes' => 'required|int|min:15|max:60',
                    'birth_date' => 'required|date',
                ],
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(422, $e->status);
            self::assertSame(['email', 'password', 'minutes', 'birth_date'], array_keys($e->errors));
            self::assertSame(['Must be at most 60.'], $e->errors['minutes']);
        }
    }

    public function testNullableKeepsExplicitNull(): void
    {
        self::assertSame(['note' => null], Validator::validate(['note' => null], ['note' => 'nullable|string|max:140']));
        self::assertSame([], Validator::validate([], ['note' => 'nullable|string']));
    }

    public function testStringLengthUsesCharactersNotBytes(): void
    {
        self::assertSame(['name' => 'Ọmọ'], Validator::validate(['name' => 'Ọmọ'], ['name' => 'string|max:3']));
    }

    public function testRegex(): void
    {
        self::assertSame(['code' => '123456'], Validator::validate(['code' => '123456'], ['code' => 'required|regex:/^\d{6}$/']));

        $this->expectException(ValidationException::class);
        Validator::validate(['code' => '12a456'], ['code' => 'required|regex:/^\d{6}$/']);
    }
}
