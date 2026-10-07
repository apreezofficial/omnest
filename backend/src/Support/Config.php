<?php

declare(strict_types=1);

namespace Omnest\Support;

/** Read-only access to config/app.php values with dot paths: $config->get('mail.from'). */
final class Config
{
    /** @param array<string, mixed> $values */
    public function __construct(private readonly array $values)
    {
    }

    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->values;
        foreach (explode('.', $path) as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }

        return $value;
    }

    public function string(string $path, string $default = ''): string
    {
        return (string) $this->get($path, $default);
    }
}
