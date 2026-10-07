<?php

declare(strict_types=1);

namespace Omnest\Services;

use Omnest\Database\Database;
use Omnest\Exceptions\ValidationException;
use Omnest\Repositories\ChildRepository;
use Omnest\Repositories\DeviceRepository;
use Omnest\Repositories\PairingCodeRepository;
use Omnest\Support\Clock;
use Omnest\Support\Config;
use RuntimeException;

/**
 * Parent creates a short-lived 6-digit code; the child phone exchanges it for its own device token.
 * Codes are stored as HMAC(APP_KEY) so a DB leak doesn't expose live codes.
 * Guessing is limited by the per-IP throttle on POST /device/pair and the 10-minute lifetime.
 */
final class PairingService
{
    public const LIFETIME_MINUTES = 10;

    public function __construct(
        private readonly Database $db,
        private readonly PairingCodeRepository $codes,
        private readonly DeviceRepository $devices,
        private readonly ChildRepository $children,
        private readonly Config $config,
    ) {
    }

    /** @return array{code: string, expires_at: string, qr_payload: string} */
    public function createCode(int $childId): array
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
            $hash = $this->hashCode($code);
            if (!$this->codes->isActiveHash($hash)) {
                $expiresAt = Clock::db('+' . self::LIFETIME_MINUTES . ' minutes');
                $this->codes->replaceForChild($childId, $hash, $expiresAt);

                return [
                    'code' => $code,
                    'expires_at' => Clock::iso($expiresAt),
                    'qr_payload' => 'omnest://pair?code=' . $code,
                ];
            }
        }

        throw new RuntimeException('Could not allocate a unique pairing code.');
    }

    /**
     * @param array{name: string, model?: ?string, os_version?: ?string, app_version?: ?string, fcm_token?: ?string} $deviceInfo
     * @return array{device: array<string, mixed>, token: string, child: array<string, mixed>}
     */
    public function pair(string $code, array $deviceInfo): array
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        return $this->db->transaction(function () use ($code, $deviceInfo) {
            $row = strlen($code) === 6 ? $this->codes->findActive($this->hashCode($code)) : null;
            if ($row === null || !$this->codes->claim((int) $row['id'])) {
                throw new ValidationException(['code' => ['That code didn\'t work. Check it, or ask your parent for a new one.']]);
            }

            $created = $this->devices->create((int) $row['child_id'], $deviceInfo);
            $this->codes->attachDevice((int) $row['id'], $created['id']);

            $device = $this->devices->findActiveByToken($created['token']);
            $child = $this->children->find((int) $row['child_id']);

            return ['device' => $device, 'token' => $created['token'], 'child' => $child];
        });
    }

    private function hashCode(string $code): string
    {
        $key = $this->config->string('app_key');
        if (strlen($key) < 32) {
            throw new RuntimeException('APP_KEY is missing or too short. Set it in .env.');
        }

        return hash_hmac('sha256', $code, $key);
    }
}
