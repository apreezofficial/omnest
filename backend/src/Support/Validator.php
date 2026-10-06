<?php

declare(strict_types=1);

namespace Omnest\Support;

use DateTimeImmutable;
use InvalidArgumentException;
use Omnest\Exceptions\ValidationException;

/**
 * Pipe-separated rules, e.g.
 *   Validator::validate($request->all(), [
 *       'email'    => 'required|email|max:191',
 *       'name'     => 'required|string|min:1|max:80',
 *       'age_tier' => 'required|in:kid,preteen,teen',
 *       'note'     => 'nullable|string|max:140',
 *   ]);
 *
 * Supported: required, nullable, string, int, numeric, bool, array, email, date (Y-m-d),
 * min:n, max:n (length for strings, value for numbers, count for arrays), in:a,b,c, regex:/.../
 * Returns only the declared fields; throws ValidationException on failure.
 */
final class Validator
{
    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     * @return array<string, mixed>
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $clean = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = self::parse($ruleString);
            $present = array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '';
            $value = $data[$field] ?? null;

            if (!$present) {
                if (array_key_exists('required', $fieldRules)) {
                    $errors[$field][] = 'This field is required.';
                } elseif (array_key_exists($field, $data) && array_key_exists('nullable', $fieldRules)) {
                    $clean[$field] = null;
                }
                continue;
            }

            $fieldErrors = [];
            foreach ($fieldRules as $rule => $arg) {
                $message = self::check($rule, $arg, $value, $fieldRules);
                if ($message !== null) {
                    $fieldErrors[] = $message;
                }
            }

            if ($fieldErrors === []) {
                $clean[$field] = is_string($value) ? trim($value) : $value;
            } else {
                $errors[$field] = $fieldErrors;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $clean;
    }

    /** @return array<string, string|null> */
    private static function parse(string $rules): array
    {
        $parsed = [];
        foreach (explode('|', $rules) as $rule) {
            if (str_starts_with($rule, 'regex:')) {
                $parsed['regex'] = substr($rule, 6);
                continue;
            }
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            $parsed[$name] = $arg;
        }

        return $parsed;
    }

    /** @param array<string, string|null> $all */
    private static function check(string $rule, ?string $arg, mixed $value, array $all): ?string
    {
        $isNumeric = array_key_exists('int', $all) || array_key_exists('numeric', $all);

        return match ($rule) {
            'required', 'nullable' => null,
            'string' => is_string($value) ? null : 'Must be text.',
            'int' => (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) ? null : 'Must be a whole number.',
            'numeric' => is_numeric($value) ? null : 'Must be a number.',
            'bool' => is_bool($value) ? null : 'Must be true or false.',
            'array' => is_array($value) ? null : 'Must be a list.',
            'email' => (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false) ? null : 'Enter a valid email address.',
            'date' => self::isDate($value) ? null : 'Use the format YYYY-MM-DD.',
            'in' => in_array((string) (is_scalar($value) ? $value : ''), explode(',', (string) $arg), true) ? null : 'Pick one of: ' . str_replace(',', ', ', (string) $arg) . '.',
            'regex' => (is_string($value) && preg_match((string) $arg, $value) === 1) ? null : 'Has an invalid format.',
            'min' => self::size($value, $isNumeric) >= (float) $arg ? null : self::sizeMessage('at least', $arg, $value, $isNumeric),
            'max' => self::size($value, $isNumeric) <= (float) $arg ? null : self::sizeMessage('at most', $arg, $value, $isNumeric),
            default => throw new InvalidArgumentException("Unknown validation rule [$rule]."),
        };
    }

    private static function size(mixed $value, bool $numeric): float
    {
        return match (true) {
            is_array($value) => count($value),
            $numeric && is_numeric($value) => (float) $value,
            is_string($value) => mb_strlen(trim($value)),
            default => 0,
        };
    }

    private static function sizeMessage(string $bound, ?string $arg, mixed $value, bool $numeric): string
    {
        return match (true) {
            is_array($value) => "Must have $bound $arg items.",
            $numeric => "Must be $bound $arg.",
            default => "Must be $bound $arg characters.",
        };
    }

    private static function isDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
