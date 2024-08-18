<?php

declare(strict_types=1);

namespace NGSOFT\IO;

class ErrorOutput extends Output
{
    public function __construct(protected ?FormatterInterface $formatter = null)
    {
        $this->stream = fopen('php://stderr', 'w+');
        parent::__construct($this->formatter);
    }
}
