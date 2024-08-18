<?php

declare(strict_types=1);

namespace NGSOFT;

use NGSOFT\IO\Buffer;
use NGSOFT\IO\ErrorOutput;
use NGSOFT\IO\FormatterInterface;
use NGSOFT\IO\GetOpt;
use NGSOFT\IO\Input;
use NGSOFT\IO\Output;
use NGSOFT\IO\OutputInterface;
use NGSOFT\IO\Style;
use NGSOFT\IO\StyleMap;
use NGSOFT\IO\TagFormatter;

class IO
{
    public const VERSION = '1.0.1';

    protected Input $input;
    protected OutputInterface $output;
    protected OutputInterface $errorOutput;
    protected FormatterInterface $formatter;
    protected StyleMap $styleMap;
    protected Buffer $buffer;
    protected GetOpt $argvParser;

    public function __construct()
    {
        $this->styleMap    = StyleMap::makeDefaultMap();
        $this->formatter   = new TagFormatter($this->styleMap);
        $this->output      = new Output($this->formatter);
        $this->errorOutput = new ErrorOutput($this->formatter);
        $this->input       = new Input();
        $this->buffer      = new Buffer();
        $this->argvParser  = new GetOpt();
    }

    /**
     * Returns the IO singleton.
     */
    public static function create(): IO
    {
        static $io = null;
        return $io ??= new static();
    }

    /**
     * Parse CLI Arguments.
     */
    public function parseOpt(?array $arguments = null): GetOpt
    {
        if ( ! $arguments && ! $this->argvParser->isEmpty())
        {
            return $this->argvParser;
        }
        return $this->argvParser->parseArguments($arguments);
    }

    /**
     * Write message using the selected style(can be a custom style) to the selected output(can be a buffer).
     */
    public function writeMessage(string|\Stringable $message, ?Style $style = null, ?OutputInterface $output = null): IO
    {
        $output ??= $this->output;

        if ($style)
        {
            $message = $style->format($message);
        }
        $output->write($message);
        return $this;
    }

    /**
     * Writes messages to the buffer.
     */
    public function write(string|\Stringable ...$messages): IO
    {
        $this->buffer->write(...$messages);
        return $this;
    }

    /**
     * Writes line to the buffer.
     */
    public function writeLn(string|\Stringable $message): IO
    {
        $this->buffer->writeLn($message);
        return $this;
    }

    /**
     * Renders buffered contents to the selected output
     * And clears the buffer.
     */
    public function render(OutputInterface $output, string|\Stringable ...$messages): IO
    {
        $this->write(...$messages);
        $this->buffer->render($output);

        return $this;
    }

    /**
     * Renders buffered contents to the stdout
     * And clears the buffer.
     */
    public function out(string|\Stringable ...$messages): IO
    {
        return $this->render($this->output, ...$messages);
    }

    /**
     * Renders buffered contents to the stderr
     * And clears the buffer.
     */
    public function err(string|\Stringable ...$messages): IO
    {
        return $this->render($this->errorOutput, ...$messages);
    }

    /**
     * Prints directly to the stdout.
     */
    public function print(string|\Stringable ...$messages): IO
    {
        $this->output->write(...$messages);
        return $this;
    }

    /**
     * Prints a line directly to the stdout.
     */
    public function printLn(string|\Stringable $message): IO
    {
        $this->output->writeLn($message);
        return $this;
    }

    /**
     * Prints directly to the stderr.
     */
    public function errPrint(string|\Stringable ...$messages): IO
    {
        $this->errorOutput->write(...$messages);
        return $this;
    }

    /**
     * Prints a line directly to the stderr.
     */
    public function errPrintLn(string|\Stringable $message): IO
    {
        $this->errorOutput->writeLn($message);
        return $this;
    }

    /**
     * Capture a line from the stdin.
     */
    public function readLn(bool $allowEmpty = true): string
    {
        return $this->input->readLn($allowEmpty);
    }

    /**
     * Capture multiple lines from the stdin.
     */
    public function read(int $lines = 1, bool $allowEmptyLines = true): array
    {
        return $this->input->read($lines, $allowEmptyLines);
    }

    public function getStyleMap(): StyleMap
    {
        return $this->styleMap;
    }

    public function setStyleMap(StyleMap $styleMap): IO
    {
        $this->styleMap = $styleMap;
        return $this;
    }

    public function getBuffer(): Buffer
    {
        return $this->buffer;
    }

    public function setBuffer(Buffer $buffer): IO
    {
        $this->buffer = $buffer;
        return $this;
    }

    public function getInput(): Input
    {
        return $this->input;
    }

    public function getOutput(): Output
    {
        return $this->output;
    }

    public function setInput(Input $input): IO
    {
        $this->input = $input;
        return $this;
    }

    public function setOutput(OutputInterface $output): IO
    {
        $this->output = $output;
        return $this;
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $errorOutput): IO
    {
        $this->errorOutput = $errorOutput;
        return $this;
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    public function setFormatter(FormatterInterface $formatter): IO
    {
        $this->formatter = $formatter;
        return $this;
    }

    public function getArgvParser(): GetOpt
    {
        return $this->argvParser;
    }

    public function setArgvParser(GetOpt $argvParser): IO
    {
        $this->argvParser = $argvParser;
        return $this;
    }
}
