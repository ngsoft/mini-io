<?php

declare(strict_types=1);

namespace NGSOFT\IO;

class CustomTag implements \Stringable, \IteratorAggregate
{
    /**
     * @var callable[]|string[]
     */
    protected array $actions = [];

    public function __construct(
        protected readonly string $name
    ) {}

    public function __toString(): string
    {
        $result = '';

        foreach ($this->actions as $action)
        {
            if ( ! is_string($action))
            {
                $action = $action();
            }

            if (is_string($action))
            {
                $result .= $action;
            }
        }

        return $result;
    }

    public static function createNew(string $name, null|callable|string $action = null): static
    {
        $i = new static($name);

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
            self::createNew('br', fn () => "\n"),
            self::createNew('tab', fn () => '    '),
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

    public function getIterator(): \Traversable
    {
        yield from $this->actions;
    }
}
