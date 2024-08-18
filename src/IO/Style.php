<?php

namespace NGSOFT\IO;

use NGSOFT\DataStructure\ReversibleIterator;
use NGSOFT\DataStructure\Set;
use NGSOFT\DataStructure\Sort;
use NGSOFT\Traits\ReversibleIteratorTrait;

class Style implements FormatterInterface, ReversibleIterator
{
    use ReversibleIteratorTrait;

    /**
     * @var Set<int>
     */
    protected Set $styles;

    protected string $label     = '';
    protected ?string $compiled = null;

    public function __construct()
    {
        $this->styles = new Set();
    }

    public static function make(BackgroundColor|Color|Format|HighBackgroundColor|HighColor ...$styles): static
    {
        $style = new static();

        foreach ($styles as $enum)
        {
            $style->addStyle($enum);
        }
        return $style;
    }

    public function addStyle(BackgroundColor|Color|Format|HighBackgroundColor|HighColor $style): static
    {
        $this->styles->add($style->value);
        $this->label .= ' ' . $style->getLabel();
        $this->label    = ltrim($this->label);
        $this->compiled = null;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAnsiString(): string
    {
        if ($this->isColorSupported())
        {
            return $this->compiled;
        }
        return '';
    }

    public function format(string|\Stringable $message): string
    {
        $message = (string) $message;

        if ($this->isColorSupported())
        {
            return $this->compiled . $message . Ansi::RESET;
        }

        return $message;
    }

    public function entries(Sort $sort = Sort::ASC): iterable
    {
        yield from $this->styles->entries($sort);
    }

    public function count(): int
    {
        return count($this->styles);
    }

    protected function compile(): void
    {
        if ($this->compiled)
        {
            return;
        }

        $this->compiled = '';

        foreach ($this->styles as $int)
        {
            $this->compiled .= sprintf(Ansi::STYLE, "{$int}");
        }
    }

    protected function isColorSupported(): bool
    {
        if (count($this->styles))
        {
            $this->compile();
            return Terminal::colorSupport() || Terminal::isColorForced();
        }
        return false;
    }
}
