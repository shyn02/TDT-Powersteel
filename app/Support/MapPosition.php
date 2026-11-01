<?php

namespace App\Support;

/**
 * A Product Application's circle centre, as a percentage of the map artwork.
 *
 * Owns the rules the admin preview and the public circle must agree on:
 * an unset value means the centre of the map, values outside 0–100 are not a
 * position (but still render inside the artwork so the editor can see where
 * they went), and percentages print with at most the two decimals the
 * database stores.
 */
final class MapPosition
{
    public const DEFAULT_PERCENT = 50.0;

    private function __construct(
        private readonly float $x,
        private readonly float $y,
    ) {}

    public static function from(mixed $x, mixed $y): self
    {
        return new self(self::normalise($x), self::normalise($y));
    }

    public function x(): float
    {
        return $this->x;
    }

    public function y(): float
    {
        return $this->y;
    }

    /** A real position: both axes inside the artwork. */
    public function isWithinMap(): bool
    {
        return $this->x >= 0 && $this->x <= 100
            && $this->y >= 0 && $this->y <= 100;
    }

    /** Percentage for rendering, clamped so an invalid entry stays on screen. */
    public function xPercent(): string
    {
        return self::percent(max(0, min(100, $this->x)));
    }

    public function yPercent(): string
    {
        return self::percent(max(0, min(100, $this->y)));
    }

    /** Percentage exactly as entered, for pointing out an invalid value. */
    public function rawXPercent(): string
    {
        return self::percent($this->x);
    }

    public function rawYPercent(): string
    {
        return self::percent($this->y);
    }

    public static function percent(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function normalise(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : self::DEFAULT_PERCENT;
    }
}
