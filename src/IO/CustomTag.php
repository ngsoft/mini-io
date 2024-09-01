<?php

declare(strict_types=1);

namespace NGSOFT\IO;

/** @phan-file-suppress PhanInvalidFQSENInCallable */
class CustomTag implements FormatterInterface, \IteratorAggregate
{
    /**
     * @var callable[]|string[]
     */
    protected array $actions           = [];

    /**
     * @var string[]
     */
    protected array $attributes        = [];

    protected array $decodedAttributes = [];

    protected array $decodedStyles     = [];

    protected bool $supportsColor      = true;

    public function __construct(
        protected string $name,
        protected bool $usesContents = false
    ) {}

    public function __debugInfo(): array
    {
        return [
            'name'              => $this->name,
            'attributes'        => $this->attributes,
            'decodedAttributes' => $this->decodedAttributes,
            'decodedStyles'     => $this->decodedStyles,
            'usesContents'      => $this->usesContents,
            'supportsColor'     => $this->supportsColor,
            'actions'           => $this->actions,
        ];
    }

    public function supportsColor(): bool
    {
        return $this->supportsColor;
    }

    public function setSupportsColor(bool $supportsColor): CustomTag
    {
        $this->supportsColor = $supportsColor;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(iterable|string $alternatives, mixed $defaultValue = null): mixed
    {
        $name = null;

        if ( ! is_iterable($alternatives))
        {
            $alternatives = [$alternatives];
        }

        foreach ($alternatives as $alternative)
        {
            if (isset($this->decodedAttributes[$alternative]))
            {
                $name = $alternative;
                break;
            }
        }

        if ( ! isset($name))
        {
            return value($defaultValue, $this);
        }
        return $this->decodedAttributes[$name];
    }

    public function getStyle(StyleMap $styleMap): ?Style
    {
        $result = null;
        $merged = new Style();

        foreach ($this->decodedStyles as $attr)
        {
            if ($style = $styleMap->getStyle($attr))
            {
                /** @var CustomColorInterface $color */
                foreach ($style->getStyles() as $color)
                {
                    $merged->addStyle($color);
                    $result ??= $merged;
                }
            }
        }
        return $result;
    }

    public function setAttributes(array $attributes): CustomTag
    {
        $this->attributes        = $attributes;

        $this->decodedAttributes = [];

        $this->decodedStyles     = [];

        foreach ($attributes as $attr)
        {
            if ($attr === $this->name)
            {
                continue;
            }

            if ( ! preg_match('#^(.+)=(.+)$#', $attr, $matches))
            {
                $this->decodedStyles[$attr]     = $attr;
                $this->decodedAttributes[$attr] = true;
                continue;
            }
            list(, $name, $str) = $matches;
            $value              = trim($str, "'\"");

            try
            {
                $this->decodedAttributes[$name] = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException)
            {
                $this->decodedAttributes[$name] = $value;
            }
        }

        return $this;
    }

    public function getDecodedStyles(): array
    {
        return $this->decodedStyles;
    }

    public function usesContents(): bool
    {
        return $this->usesContents;
    }

    public static function createNew(string $name, null|callable|string $action = null, bool $usesContents = false): static
    {
        $i = new static($name, $usesContents);

        if ($action)
        {
            $i->addAction($action);
        }
        return $i;
    }

    public static function createBuiltin(): array
    {
        static $builtin;

        return $builtin ??= [
            self::createNew('br', "\n"),
            self::createNew('tab', '    '),
            self::createNew('hr', function (CustomTag $t)
            {
                $w          = $t->getAttribute(['length', 'len', 'width']);
                $hasPadding = true;
                $padding    = $t->getAttribute('padding');

                if ( ! is_int($padding))
                {
                    $padding    = 1;
                    $hasPadding = false;
                }
                $padding    = max(0, $padding);

                $max        = Terminal::getWidth() - ($padding * 2);

                if (is_int($w))
                {
                    $w = max($w, 0);
                } else
                {
                    $w = $max;
                }

                $w          = min($w, $max);

                if ($w < $max && ! $hasPadding)
                {
                    $padding = (int) ceil(($max - $w) / 2);
                }

                $str        = '';

                if ($w > 0)
                {
                    $str   = str_repeat('=', $w);
                    $extra = implode(' ', $t->getDecodedStyles());

                    if ( ! empty($extra))
                    {
                        $str = "<{$extra}>{$str}</>";
                    }

                    if ($padding)
                    {
                        $str = str_repeat(' ', $padding) . $str;
                    }
                }
                return "\n{$str}\n";
            }),
        ];
    }

    public function addAction(callable|string $action): static
    {
        $this->actions[] = $action;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function format(string|\Stringable $message): string
    {
        $result = '';

        foreach ($this->actions as $action)
        {
            if ( ! is_string($action))
            {
                $action = $action($this, str_val($message));
            }

            if (is_string($action))
            {
                $result .= $action;
            }
        }
        return $result;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->decodedAttributes;
    }
}
