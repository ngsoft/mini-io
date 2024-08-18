<?php

namespace NGSOFT\IO;

interface FormatterInterface
{
    public function format(string|\Stringable $message): string;
}
