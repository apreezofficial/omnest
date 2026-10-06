<?php

declare(strict_types=1);

namespace Omnest\Auth;

interface TokenResolver
{
    /**
     * Looks up the principal (parent user or paired device) for a plain bearer token.
     * Implementations must compare against the stored SHA-256 hash only.
     *
     * @return array<string, mixed>|null
     */
    public function resolve(string $plainToken): ?array;
}
