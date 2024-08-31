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

    protected bool $supportsColor      = true;

    public function __construct(
        protected string $name,
        protected bool $usesContents = false
    ) {}

    public function __debugInfo(): array
    {
        return [
            'name'          => $this->name,
            'attributes'    => $this->attributes,
            'usesContents'  => $this->usesContents,
            'supportsColor' => $this->supportsColor,
            'actions'       => $this->actions,
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

    public function getAttribute(string $name, mixed $defaultValue = null): mixed
    {
        if ( ! isset($this->decodedAttributes[$name]))
        {
            return value($defaultValue, $this);
        }
        return $this->decodedAttributes[$name];
    }

    public function setAttributes(array $attributes): CustomTag
    {
        $this->attributes        = $attributes;

        $this->decodedAttributes = [];

        foreach ($attributes as $attr)
        {
            if ( ! preg_match('#^(.+)=(.+)$#', $attr, $matches))
            {
                $this->decodedAttributes[$attr] = '';
                continue;
            }
            list(, $name, $str) = $matches;
            $value              = trim($str, "'`\"");

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
                $w = Terminal::getWidth() - 2;

                if ($w > 0)
                {
                    $str   = ' ' . str_repeat('=', $w);
                    $extra = [];

                    foreach ($t as $k => $v)
                    {
                        if ('' === $v && 'hr' !== $k)
                        {
                            $extra[] = $k;
                        }
                    }
                    $extra = implode(' ', $extra);

                    if ( ! empty($extra))
                    {
                        $str = "<{$extra}>{$str}</>";
                    }

                    return "\n{$str}\n";
                }
                return "\n\n";
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
