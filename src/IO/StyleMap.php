<?php

declare(strict_types=1);

namespace NGSOFT\IO;

use NGSOFT\DataStructure\Map;
use NGSOFT\DataStructure\Set;
use NGSOFT\DataStructure\Sort;
use NGSOFT\Traits\ReversibleIteratorTrait;

class StyleMap implements \Countable, \IteratorAggregate, \ArrayAccess
{
    use ReversibleIteratorTrait;

    protected const ALIASES  = [

        'magenta'          => 'purple',
        'bg:magenta'       => 'bg:purple',
        'magenta:bright'   => 'purple:bright',
        'bg:purple:bright' => 'bg:magenta:bright',
        'white'            => 'gray:bright',
        'aqua'             => 'cyan',
        'bg:aqua'          => 'bg:cyan',
        'aqua:bright'      => 'cyan:bright',
        'bg:aqua:bright'   => 'bg:cyan:bright',
        'em'               => 'italic',
        'strong'           => 'bold',
        's'                => 'strikethrough',
        'u'                => 'underline',
    ];
    protected const CUSTOM   = [
        ['href', Color::Cyan, Format::Underline],
        ['emergency', Color::Yellow, BackgroundColor::Red, Format::Bold],
        ['alert', Color::Red, Format::Bold],
        ['bg:alert', Color::Gray, BackgroundColor::Red, Format::Bold],
        ['critical', Color::Red, Format::Bold],
        ['bg:critical', Color::Gray, BackgroundColor::Red, Format::Bold],
        ['error', Color::Red],
        ['bg:error', Color::Gray, BackgroundColor::Red],
        ['warning', Color::Yellow],
        ['bg:warning', Color::Black, BackgroundColor::Yellow],
        ['notice', Color::Cyan],
        ['bg:notice', Color::Gray, BackgroundColor::Cyan],
        ['info', Color::Green],
        ['success', Color::Green, Format::Bold],
        ['bg:success', Color::Gray, BackgroundColor::Green, Format::Bold],
        ['bg:info', Color::Gray, BackgroundColor::Green],
        ['debug', Color::Purple],
        ['bg:debug', BackgroundColor::Purple, Color::Gray],
        ['comment', Color::Yellow],
        ['whisper', Color::Gray, Format::Dim],
        ['shout', Color::Red, Format::Bold],
    ];

    protected Map $styles;

    protected array $aliases = [];

    protected Set $handlers;

    public function __construct()
    {
        $this->styles   = new Map();
        $this->handlers = new Set();
    }

    public function __debugInfo(): array
    {
        return [
            'styles' => $this->styles,
        ];
    }

    public static function makeDefaultMap(): static
    {
        static $cache;

        if ( ! isset($cache))
        {
            $map          = new static();

            foreach (Color::cases() as $enum)
            {
                $map->addStyle(Style::make($enum));
            }

            foreach (BackgroundColor::cases() as $enum)
            {
                $map->addStyle(Style::make($enum));
            }

            foreach (HighColor::cases() as $enum)
            {
                $map->addStyle(Style::make($enum));
            }

            foreach (HighBackgroundColor::cases() as $enum)
            {
                $map->addStyle(Style::make($enum));
            }

            foreach (Format::cases() as $enum)
            {
                $map->addStyle(Style::make($enum));
            }

            $map->aliases = self::ALIASES;

            foreach (self::CUSTOM as $item)
            {
                $label = array_shift($item);
                $map->addStyle(Style::make(...$item), $label);
            }

            // add rgb handler (creates colors on the fly
            $map
                ->addHandler([RgbColor::class, 'colorFilter'])
                ->addHandler([Xterm256::class, 'colorFilter'])
            ;

            $cache        = $map;
        }

        return $cache;
    }

    public function hasStyle(string $label): bool
    {
        return $this->styles->has($label);
    }

    public function addStyle(Style $style, ?string $label = null): static
    {
        $label ??= $style->getLabel();

        if ($label)
        {
            $this->styles->add($label, $style);
        }
        return $this;
    }

    public function addHandler(callable $handler): static
    {
        $this->handlers->add($handler);

        return $this;
    }

    public function addAlias(string $alias, string $label): static
    {
        $this->aliases[$alias] = $label;

        return $this;
    }

    public function getStyle(string $label): ?Style
    {
        $label = $this->aliases[$label] ?? $label;
        $value = $this->styles->get($label);

        if ( ! $value)
        {
            foreach ($this->handlers as $handler)
            {
                $result = $handler($label);

                if ($result instanceof CustomColorInterface)
                {
                    $this->addStyle($value = Style::make($result), $label);
                    break;
                }
            }
        }
        return $value;
    }

    /**
     * @return Style[]
     */
    public function getStyles(string ...$labels): array
    {
        $result = [];

        foreach ($labels as $label)
        {
            if ($style = $this->getStyle($label))
            {
                $result[$label] = $style;
            }
        }

        return $result;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->styles->has($this->aliases[$offset] ?? $offset);
    }

    public function offsetGet(mixed $offset): ?Style
    {
        return $this->styles->get($this->aliases[$offset] ?? $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof Style)
        {
            $this->styles->set($this->aliases[$offset] ?? $offset, $value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->styles->delete($this->aliases[$offset] ?? $offset);
    }

    public function entries(Sort $sort = Sort::ASC): iterable
    {
        yield from $this->styles->entries($sort);
    }
}
