<?php

declare(strict_types=1);

namespace NGSOFT\IO;

interface RendererInterface
{
    /**
     * Render to the Output.
     */
    public function render(OutputInterface $output): void;
}
