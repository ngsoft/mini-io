<?php

declare(strict_types=1);

namespace NGSOFT\IO;

use NGSOFT\DataStructure\Map;

class TagFormatter implements FormatterInterface
{
    protected Buffer $buffer;
    /**
     * @var Map<string,CustomTag>
     */
    protected Map $customTags;

    public function __construct(protected ?StyleMap $styleMap = null)
    {
        $this->styleMap ??= StyleMap::makeDefaultMap();
        $this->buffer     = new Buffer();
        $this->customTags = new Map();

        foreach (CustomTag::createBuiltin() as $tag)
        {
            $this->addCustomTag($tag);
        }
    }

    /**
     * Add a custom tag independent of style.
     */
    public function addCustomTag(CustomTag $tag, ?string $label = null): static
    {
        $label ??= $tag->getName();

        if ( ! $this->styleMap->hasStyle($label))
        {
            $this->customTags->add($label, $tag);
        }
        return $this;
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
            $labels  = trim($labels);

            if ($this->customTags->has($labels))
            {
                // if tags are added
                $message = strval($this->customTags->get($labels)) . $message;
                continue;
            }

            if ( ! $colors)
            {
                continue;
            }

            if (str_starts_with($labels, '/'))
            {
                $this->buffer->write(Ansi::RESET);
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
