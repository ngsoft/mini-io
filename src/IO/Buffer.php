<?php

declare(strict_types=1);

namespace NGSOFT\IO;

use NGSOFT\DataStructure\ReversibleIterator;
use NGSOFT\DataStructure\Sort;
use NGSOFT\Traits\ReversibleIteratorTrait;

class Buffer implements RendererInterface, OutputInterface, ReversibleIterator, \Stringable
{
    use ReversibleIteratorTrait;

    /**
     * @var string[]
     */
    protected array $buffer = [];

    public function __toString(): string
    {
        return $this->pullString();
    }

    public function clear(): void
    {
        $this->buffer = [];
    }

    /**
     * Pull and erase the buffer.
     */
    public function pull(): array
    {
        try
        {
            return $this->buffer;
        } finally
        {
            $this->clear();
        }
    }

    public function pullString(): string
    {
        return implode('', $this->pull());
    }

    public function render(OutputInterface $output): void
    {
        if ($output === $this)
        {
            throw new \LogicException('Buffer cannot render using itself: infinite loop');
        }

        $output->write(...$this->pull());
    }

    public function write(string|\Stringable ...$messages): void
    {
        foreach ($messages as $message)
        {
            $this->buffer[] = (string) $message;
        }
    }

    public function writeLn(string|\Stringable $message): void
    {
        $this->write($message, "\n");
    }

    public function count(): int
    {
        return count($this->buffer);
    }

    public function entries(Sort $sort = Sort::ASC): iterable
    {
        $entries = $this->pull();

        if (Sort::DESC === $sort)
        {
            $entries = array_reverse($entries);
        }

        yield from $entries;
    }
}
