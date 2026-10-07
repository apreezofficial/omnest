<?php

declare(strict_types=1);

namespace Omnest\Auth;

use Omnest\Repositories\DeviceRepository;

final class DeviceTokenResolver implements TokenResolver
{
    public function __construct(private readonly DeviceRepository $devices)
    {
    }

    public function resolve(string $plainToken): ?array
    {
        if (!str_starts_with($plainToken, Tokens::DEVICE)) {
            return null;
        }

        $device = $this->devices->findActiveByToken($plainToken);
        if ($device === null) {
            return null;
        }

        $this->devices->touch((int) $device['id'], $device['last_seen_at']);

        return $device;
    }
}
