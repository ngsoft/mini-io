<?php

declare(strict_types=1);

namespace NGSOFT\IO;

interface CustomColorInterface
{
    public function getValue(): int|string;

    public function getLabel(): string;

    public function getName(): string;
}
