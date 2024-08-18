<?php

namespace NGSOFT\IO;

use NGSOFT\Enums\EnumTrait;

enum BackgroundColor: int
{
    use EnumTrait;

    case Black  = 40;
    case Red    = 41;
    case Green  = 42;
    case Yellow = 43;
    case Blue   = 44;
    case Purple = 45;
    case Cyan   = 46;
    case Gray   = 47;

    public function getLabel(): string
    {
        return 'bg:' . strtolower($this->getName());
    }
}
