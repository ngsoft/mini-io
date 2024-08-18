<?php

namespace NGSOFT\IO;

use NGSOFT\Enums\EnumTrait;

enum HighBackgroundColor: int
{
    use EnumTrait;

    case Black  = 100;
    case Red    = 101;
    case Green  = 102;
    case Yellow = 103;
    case Blue   = 104;
    case Purple = 105;
    case Cyan   = 106;
    case Gray   = 107;

    public function getLabel(): string
    {
        return 'bg:' . strtolower($this->getName()) . ':bright';
    }
}
