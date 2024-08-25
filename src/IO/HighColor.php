<?php

declare(strict_types=1);

namespace NGSOFT\IO;

use NGSOFT\Enums\EnumTrait;

enum HighColor: int implements CustomColorInterface
{
    use EnumTrait;

    case Black  = 90;
    case Red    = 91;
    case Green  = 92;
    case Yellow = 93;
    case Blue   = 94;
    case Purple = 95;
    case Cyan   = 96;
    case Gray   = 97;

    public function getLabel(): string
    {
        return strtolower($this->getName()) . ':bright';
    }
}
