<?php

namespace NGSOFT\IO;

use NGSOFT\Enums\EnumTrait;

enum Format: int
{
    use EnumTrait;

    case Bold         = 1;
    case Dim          = 2;
    case Italic       = 3;
    case Underline    = 4;
    case Underline2   = 21;
    case Blink        = 5;
    case BlinkAlt     = 6;
    case Inverse      = 7;
    case Hidden       = 8;
    case StrikeTrough = 9;
    case Reset        = 0;

    public function getLabel(): string
    {
        return strtolower($this->getName());
    }
}
