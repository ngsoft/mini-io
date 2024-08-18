<?php

namespace NGSOFT\IO;

interface RendererInterface
{
    /**
     * Render to the Output.
     */
    public function render(OutputInterface $output): void;
}
