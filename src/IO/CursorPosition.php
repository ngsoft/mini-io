<?php

declare(strict_types=1);

namespace NGSOFT\IO;

final class CursorPosition
{
    public function __construct(
        public readonly int $x,
        public readonly int $y
    ) {}

    public static function createNew(int $x, int $y): CursorPosition
    {
        return new CursorPosition($x, $y);
    }

    public function equals(CursorPosition $position): bool
    {
        return $this->x === $position->x && $this->y === $position->y;
    }
}
