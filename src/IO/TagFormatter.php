<?php

namespace NGSOFT\IO;

class TagFormatter implements FormatterInterface
{
    protected Buffer $buffer;

    public function __construct(protected ?StyleMap $styleMap = null)
    {
        $this->styleMap ??= StyleMap::makeDefaultMap();
        $this->buffer = new Buffer();
    }

    public function format(string|\Stringable $message): string
    {
        $message = (string) $message;

        $colors  = Terminal::colorSupport() || Terminal::isColorForced();

        while (preg_match('#<([^>]*)>#', $message, $matches, PREG_OFFSET_CAPTURE) > 0)
        {
            /** @var int $offset */
            $input   = $matches[0][0];
            $labels  = $matches[1][0];
            $len     = strlen($input);

            $offset  = $matches[0][1];
            $this->buffer->write(substr($message, 0, $offset));
            $message = substr($message, $offset + $len);

            if ( ! $colors)
            {
                continue;
            }

            $labels  = trim($labels);

            if ('/' === $labels)
            {
                $this->buffer->write(Ansi::RESET);
                continue;
            }

            if ('br' === $labels)
            {
                $this->buffer->write("\n");
                continue;
            }

            foreach (preg_split('#\h+#', $labels) as $label)
            {
                if ($style = $this->styleMap->getStyle($label))
                {
                    $this->buffer->write($style->getAnsiString());
                }
            }
        }

        $message = $this->buffer->pullString() . $message;

        return str_replace(['&gt;', '&lt;'], ['>', '<'], $message);
    }
}
