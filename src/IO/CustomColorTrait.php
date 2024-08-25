<?php

declare(strict_types=1);

namespace NGSOFT\IO;

trait CustomColorTrait
{
    protected bool $foreground = true;
    protected string $name     = '';

    public function getLabel(): string
    {
        if ( ! $this->foreground)
        {
            return 'bg:' . $this->getName();
        }
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected static function assertValidRange($rgb): void
    {
        if ( ! in_range($rgb, 0, 255))
        {
            throw new \InvalidArgumentException("Invalid range value {$rgb}, not between 0 and 255");
        }
    }
}
