<?php

namespace NGSOFT\IO;

class NullFormatter implements FormatterInterface
{
    public function format(string|\Stringable $message): string
    {
        return (string) $message;
    }
}
