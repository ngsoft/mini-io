<?php

declare(strict_types=1);

namespace NGSOFT\IO;

use NGSOFT\IO;

/**
 * @property int  $x
 * @property int  $y
 * @property bool $enabled
 */
class Cursor implements \Stringable, RendererInterface
{
    protected const TAG_ACTIONS = [
        'up'          => 'moveUp',
        'down'        => 'moveDown',
        'left'        => 'moveLeft',
        'right'       => 'moveRight',
        'clear'       => 'clearAll',
        'clear:down'  => 'clearDown',
        'clear:up'    => 'clearUp',
        'clear:line'  => 'clearLine',
        'clear:end'   => 'clearRight',
        'clear:start' => 'clearLeft',
    ];

    protected Buffer $buffer;
    protected Input $input;

    public function __construct(protected ?IO $io = null)
    {
        $this->io ??= IO::create();
        $this->buffer = new Buffer();
        $this->input  = $this->io->getInput();

        // run isEnabled at(1,1)
        if ('cli' === php_sapi_name())
        {
            $this->isEnabled();
        }
    }

    public function __get(string $name)
    {
        return match ($name)
        {
            'x'       => $this->getX(),
            'y'       => $this->getY(),
            'enabled' => $this->isEnabled(),
            default   => null
        };
    }

    public function __isset(string $name): bool
    {
        return null !== $this->__get($name);
    }

    public function __set(string $name, $value): void
    {
        // noop
    }

    public function __unset(string $name): void
    {
        // noop
    }

    public function __debugInfo(): array
    {
        $pos = $this->getCursorPosition();
        return [
            'x'       => $pos->x,
            'y'       => $pos->y,
            'enabled' => $this->isEnabled(),
        ];
    }

    public function __invoke(CustomTag $tag): string
    {
        $method = self::TAG_ACTIONS[$tag->getName()] ?? null;

        if ($method)
        {
            return str_val($this->{$method}());
        }
        return '';
    }

    public function __toString(): string
    {
        return str_val($this->buffer);
    }

    public function addTagActions(TagFormatter $tagFormatter): static
    {
        foreach (array_keys(self::TAG_ACTIONS) as $tag)
        {
            $tagFormatter->addCustomTag(
                CustomTag::createNew($tag, $this)
            );
        }

        return $this;
    }

    public function render(OutputInterface $output): void
    {
        $this->buffer->render($output);
    }

    public function isEnabled(): bool
    {
        static $result = null;

        if ( ! isset($result))
        {
            $out      = $this->io->getOutput();
            $w        = Terminal::getWidth();
            $currentX = $this->getX();

            do
            {
                $x = rand(5, $w - 1);
            } while ($x === $currentX);

            // move to col
            $out->write(
                Ansi::CURSOR_HIDE,
                sprintf(Ansi::CURSOR_COL, $x)
            );
            // get new position
            $pos      = $this->getCursorPosition();
            // restore previous col
            $out->write(
                sprintf(Ansi::CURSOR_COL, $currentX),
                Ansi::CURSOR_SHOW
            );
            // check if new position is expected value
            $result   = $x === $pos->x;
        }

        return $result;
    }

    public function getCursorPosition(): CursorPosition
    {
        $x = $y = 1;

        if (Terminal::supportsPowershell())
        {
            $y = intval(trim(shell_exec('powershell.exe $Host.UI.RawUI.CursorPosition.Y') ?? '0')) + 1;
            $x = intval(trim(shell_exec('powershell.exe $Host.UI.RawUI.CursorPosition.X') ?? '0')) + 1;
            return new CursorPosition($x, $y);
        }

        if (Terminal::ttySupport() && is_string($mode = shell_exec('stty -g')))
        {
            $top   = $left = null;
            $input = $this->input->getStream();
            shell_exec('stty -icanon -echo');
            @fwrite($input, Ansi::CURSOR_READ_POS);
            $code  = fread($input, 1024);
            shell_exec(sprintf('stty %s', $mode));
            @sscanf($code, "\x1b[%d;%dR", $top, $left);

            if (is_numeric($top))
            {
                $y = intval($top);
                $x = intval($left);
            }
        }

        return new CursorPosition($x, $y);
    }

    public function save(): static
    {
        // printing escape sequence on unsupported term can print out a broken string
        if ($this->isEnabled())
        {
            $this->buffer->write(Ansi::CURSOR_SAVE);
        }

        return $this;
    }

    public function load(): static
    {
        // printing escape sequence on unsupported term can print out a broken string
        if ($this->isEnabled())
        {
            $this->buffer->write(Ansi::CURSOR_LOAD);
        }

        return $this;
    }

    public function getX(): int
    {
        return $this->getCursorPosition()->x;
    }

    public function getY(): int
    {
        return $this->getCursorPosition()->y;
    }

    public function setX(int $x): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::CURSOR_COL,
                max($x, 1)
            )
        );
        return $this;
    }

    public function setY(int $y): static
    {
        $pos = $this->getCursorPosition();

        if ($y === $pos->y)
        {
            return $this;
        }
        return $this->setPosition(
            CursorPosition::createNew(
                $pos->x,
                $y
            )
        );
    }

    public function setPosition(CursorPosition $position): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::CURSOR_POS,
                max(1, $position->y),
                max(1, $position->x)
            )
        );

        return $this;
    }

    public function moveUp(int $lines = 1): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::CURSOR_UP,
                max(1, $lines)
            )
        );

        return $this;
    }

    public function moveDown(int $lines = 1): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::CURSOR_DOWN,
                max(1, $lines)
            )
        );

        return $this;
    }

    public function moveRight(int $lines = 1): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::CURSOR_RIGHT,
                max(1, $lines)
            )
        );

        return $this;
    }

    public function moveLeft(int $lines = 1): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::CURSOR_LEFT,
                max(1, $lines)
            )
        );

        return $this;
    }

    public function moveStartDown(int $lines = 1): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::CURSOR_NEXT_LINE,
                max(1, $lines)
            )
        );

        return $this;
    }

    public function moveStartUp(int $lines = 1): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::CURSOR_PREV_LINE,
                max(1, $lines)
            )
        );

        return $this;
    }

    public function scrollUp(int $lines = 1): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::SCROLL_UP,
                max(1, $lines)
            )
        );

        return $this;
    }

    public function scrollDown(int $lines = 1): static
    {
        $this->buffer->write(
            sprintf(
                Ansi::SCROLL_DOWN,
                max(1, $lines)
            )
        );

        return $this;
    }

    public function hide(): static
    {
        $this->buffer->write(
            Ansi::CURSOR_HIDE
        );

        return $this;
    }

    public function show(): static
    {
        $this->buffer->write(
            Ansi::CURSOR_SHOW
        );

        return $this;
    }

    public function clearDown(): static
    {
        $this->buffer->write(
            Ansi::RESET,
            Ansi::CLEAR_DOWN
        );

        return $this;
    }

    public function clearUp(): static
    {
        $this->buffer->write(
            Ansi::RESET,
            Ansi::CLEAR_UP
        );

        return $this;
    }

    public function clearAll(): static
    {
        $this->buffer->write(
            Ansi::RESET,
            Ansi::CLEAR_SCREEN
        );

        return $this;
    }

    public function clearLine(): static
    {
        $this->buffer->write(
            Ansi::RESET,
            Ansi::CLEAR_LINE
        );

        return $this;
    }

    public function clearRight(): static
    {
        $this->buffer->write(
            Ansi::RESET,
            Ansi::CLEAR_END_LINE
        );

        return $this;
    }

    public function clearLeft(): static
    {
        $this->buffer->write(
            Ansi::RESET,
            Ansi::CLEAR_START_LINE
        );

        return $this;
    }
}
