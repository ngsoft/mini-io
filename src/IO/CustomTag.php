<?php

declare(strict_types=1);

namespace NGSOFT\IO;

/** @phan-file-suppress PhanInvalidFQSENInCallable */
class CustomTag implements FormatterInterface
{
    /**
     * @var callable[]|string[]
     */
    protected array $actions      = [];

    /**
     * @var string[]
     */
    protected array $attributes   = [];

    protected bool $supportsColor = true;

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

    public function setAttributes(array $attributes): CustomTag
    {
        $this->attributes = $attributes;
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
            self::createNew('hr', function ()
            {
                $w = Terminal::getWidth() - 1;

                if ($w > 0)
                {
                    return sprintf("\n%s\n", str_repeat('=', $w));
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
}
