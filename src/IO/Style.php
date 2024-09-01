<?php

declare(strict_types=1);

namespace NGSOFT\IO;

use NGSOFT\DataStructure\Map;
use NGSOFT\DataStructure\ReversibleIterator;
use NGSOFT\DataStructure\Sort;
use NGSOFT\Traits\ReversibleIteratorTrait;

class Style implements FormatterInterface, ReversibleIterator
{
    use ReversibleIteratorTrait;

    /**
     * @var Map<CustomColorInterface,int|string>
     */
    protected Map $styles;

    protected string $label   = '';
    protected ?string $prefix = null;

    protected string $suffix  = Ansi::RESET;

    public function __construct()
    {
        $this->styles = new Map();
    }

    public function __debugInfo(): array
    {
        return [
            'label'  => $this->label,
            'styles' => $this->styles,
            'format' => $this->format('format'),
        ];
    }

    public static function make(CustomColorInterface ...$styles): static
    {
        $style = new static();

        foreach ($styles as $enum)
        {
            $style->addStyle($enum);
        }
        return $style;
    }

    public function addStyle(CustomColorInterface $style): static
    {
        $this->styles->add($style, $style->getValue());
        $this->label .= ' ' . $style->getLabel();
        $this->label  = ltrim($this->label);
        $this->prefix = null;
        return $this;
    }

    /**
     * @return \Traversable<CustomColorInterface>
     */
    public function getStyles(Sort $sort = Sort::ASC): iterable
    {
        yield from $this->styles->keys($sort);
    }

    public function setLabel(string $label): Style
    {
        $this->label = $label;
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
            return $this->prefix;
        }
        return '';
    }

    public function getFormatString(): string
    {
        if ($this->isColorSupported())
        {
            return $this->prefix . '%s' . $this->suffix;
        }
        return '%s';
    }

    public function format(string|\Stringable $message): string
    {
        $message = (string) $message;
        return sprintf($this->getFormatString(), $message);
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
        if ($this->prefix)
        {
            return;
        }

        $this->prefix = '';

        foreach ($this->styles as $value)
        {
            $this->prefix .= sprintf(Ansi::STYLE, "{$value}");
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
