<?php

declare(strict_types=1);

namespace NGSOFT\IO;

interface FormatterInterface
{
    public function format(string|\Stringable $message): string;
}
