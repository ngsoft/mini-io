<?php

namespace NGSOFT\IO;

class Output implements OutputInterface
{
    /** @var ?resource */
    protected $stream;
    protected ?FormatterInterface $formatter = null;

    public function __construct(?FormatterInterface $formatter = null)
    {
        $this->formatter = $formatter ?? new NullFormatter();

        if ( ! isset($this->stream))
        {
            $this->stream ??= fopen('php://stdout', 'w+');
        }
    }

    public function __destruct()
    {
        if ($this->stream)
        {
            @fclose($this->stream);
        }
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    public function write(string|\Stringable ...$messages): void
    {
        $formatter = &$this->formatter;

        foreach ($messages as $message)
        {
            $message = $formatter->format($message);
            @fwrite($this->stream, $message);
            fflush($this->stream);
        }
    }

    public function writeLn(string|\Stringable $message): void
    {
        $this->write($message, "\n");
    }
}
