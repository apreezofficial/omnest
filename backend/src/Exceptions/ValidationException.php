<?php

declare(strict_types=1);

namespace Omnest\Exceptions;

final class ValidationException extends HttpException
{
    /** @param array<string, list<string>> $errors field => messages */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(422, 'validation_failed', 'Some fields need fixing.', ['fields' => $errors]);
    }
}
