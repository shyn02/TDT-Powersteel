<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Canonical checksum contract for legacy `projects` rows (spec §4.5).
 *
 * The checksum is the lowercase SHA-256 of a UTF-8 JSON array holding the
 * legacy scalars in exactly this order:
 *
 *   date_added, contractor, project_name, value, status, is_priority,
 *   encoded_by, created_at, deleted_at
 *
 * Canonical forms:
 * - `date_added` → `Y-m-d` text, or JSON null.
 * - `value` → two-decimal fixed-point text (e.g. `1500.50`), or JSON null.
 * - `created_at` / `deleted_at` → UTC ISO-8601 text (`Y-m-d\TH:i:s\Z`), or
 *   JSON null. Naive (timezone-less) strings are interpreted as UTC so a
 *   timestamp read back from the archive matches the instant computed from
 *   the source model regardless of the application timezone (Asia/Manila).
 * - `is_priority` → JSON boolean, or JSON null.
 * - `contractor`, `project_name`, `status` → verbatim text, or JSON null.
 * - `encoded_by` → integer, or JSON null.
 *
 * JSON encoding always uses `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`
 * so Unicode and slashes round-trip byte-identically, and
 * `JSON_THROW_ON_ERROR` so malformed input fails loudly instead of being
 * silently archived.
 */
class LegacyProjectChecksum
{
    /**
     * Canonical field order. This order is part of the checksum contract;
     * reordering fields changes the checksum by design.
     *
     * @var list<string>
     */
    public const FIELDS = [
        'date_added',
        'contractor',
        'project_name',
        'value',
        'status',
        'is_priority',
        'encoded_by',
        'created_at',
        'deleted_at',
    ];

    /**
     * Build the canonical payload for a legacy project row.
     *
     * Accepts an Eloquent model, stdClass, or plain array; missing keys
     * canonicalize to JSON null.
     *
     * @param  array<string, mixed>|object  $row
     * @return array<string, mixed>
     */
    public static function payload(array|object $row): array
    {
        $get = static function (string $key) use ($row): mixed {
            if (is_array($row)) {
                return $row[$key] ?? null;
            }

            return $row->{$key} ?? null;
        };

        return [
            'date_added' => self::canonicalDate($get('date_added')),
            'contractor' => self::canonicalString($get('contractor')),
            'project_name' => self::canonicalString($get('project_name')),
            'value' => self::canonicalValue($get('value')),
            'status' => self::canonicalString($get('status')),
            'is_priority' => self::canonicalBool($get('is_priority')),
            'encoded_by' => self::canonicalInt($get('encoded_by')),
            'created_at' => self::canonicalTimestamp($get('created_at'), 'created_at'),
            'deleted_at' => self::canonicalTimestamp($get('deleted_at'), 'deleted_at'),
        ];
    }

    /**
     * Lowercase SHA-256 over the canonical JSON array.
     *
     * @param  array<string, mixed>  $payload  In canonical field order.
     */
    public static function checksum(array $payload): string
    {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        return strtolower(hash('sha256', $json));
    }

    private static function canonicalDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            try {
                $parsed = new DateTimeImmutable((string) $value);
            } catch (\Exception $e) {
                throw new InvalidArgumentException("Invalid date_added value [{$value}].", 0, $e);
            }

            return $parsed->format('Y-m-d');
        }

        throw new InvalidArgumentException('Invalid date_added value; expected date, string, or null.');
    }

    private static function canonicalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        throw new InvalidArgumentException('Invalid string value; expected scalar or null.');
    }

    private static function canonicalValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Invalid value [{$value}]; expected numeric or null.");
        }

        return number_format((float) $value, 2, '.', '');
    }

    private static function canonicalBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    private static function canonicalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Invalid encoded_by value; expected integer-like or null.");
        }

        return (int) $value;
    }

    private static function canonicalTimestamp(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof DateTimeInterface) {
                $instant = DateTimeImmutable::createFromInterface($value);
            } elseif (is_int($value) || is_float($value)) {
                $instant = new DateTimeImmutable('@'.(int) $value);
            } else {
                // Naive strings are interpreted as UTC so archive
                // round-trips preserve the instant under any app timezone.
                // Strings carrying an explicit offset keep that offset.
                $instant = new DateTimeImmutable((string) $value, new DateTimeZone('UTC'));
            }
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid {$field} value; expected datetime, string, or null.", 0, $e);
        }

        return $instant->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
