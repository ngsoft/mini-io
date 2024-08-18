<?php

namespace NGSOFT\IO;

use NGSOFT\DataStructure\Map;

class StyleMap
{
    protected const ALIASES  = [
        'purple'           => 'magenta',
        'bg:purple'        => 'bg:magenta',
        'purple:bright'    => 'magenta:bright',
        'bg:purple:bright' => 'bg:magenta:bright',
        'gray:bright'      => 'white',
        'aqua'             => 'cyan',
        'bg:aqua'          => 'bg:cyan',
        'aqua:bright'      => 'cyan:bright',
        'bg:aqua:bright'   => 'bg:cyan:bright',
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

    public function __construct()
    {
        $this->styles = new Map();
    }

    public static function makeDefaultMap(): static
    {
        static $map;

        if ( ! $map)
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
        }

        return $map;
    }

    public function addStyle(Style $style, ?string $label = null): static
    {
        $label ??= $style->getLabel();

        if ($label)
        {
            $this->styles->set($label, $style);
        }
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
        return $this->styles->get($label);
    }
}
