<?php

declare(strict_types=1);

namespace NGSOFT\IO;

interface OutputInterface
{
    /**
     * Write messages to the output.
     */
    public function write(string|\Stringable ...$messages): void;

    /**
     * Write message to the output and creates a new line.
     */
    public function writeLn(string|\Stringable $message): void;
}
