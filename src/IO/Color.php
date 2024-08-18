<?php

namespace NGSOFT\IO;

use NGSOFT\Enums\EnumTrait;

enum Color: int
{
    use EnumTrait;

    case Black  = 30;
    case Red    = 31;
    case Green  = 32;
    case Yellow = 33;
    case Blue   = 34;
    case Purple = 35;
    case Cyan   = 36;
    case Gray   = 37;

    public function getBackgroundValue(): int
    {
        return $this->value + 10;
    }

    public function getLabel(): string
    {
        return strtolower($this->getName());
    }
}
