<?php

declare(strict_types=1);

namespace Omnest\Auth;

use Omnest\Repositories\ApiTokenRepository;

final class ParentTokenResolver implements TokenResolver
{
    public function __construct(private readonly ApiTokenRepository $tokens)
    {
    }

    public function resolve(string $plainToken): ?array
    {
        if (!str_starts_with($plainToken, Tokens::PARENT)) {
            return null;
        }

        $row = $this->tokens->findActive($plainToken);
        if ($row === null) {
            return null;
        }

        $this->tokens->touch((int) $row['token_id'], $row['last_used_at']);

        return $row;
    }
}
