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
        $this->buffer = new Buffer();
        $this->customTags = new Map();

        foreach (CustomTag::createBuiltin() as $tag) {
            $this->addCustomTag($tag);
        }
    }

    public function __clone(): void
    {
        $this->customTags = clone $this->customTags;
        $this->buffer = new Buffer();
    }

    /**
     * Add a custom tag independent of style.
     */
    public function addCustomTag(CustomTag $tag, ?string $label = null): static
    {
        $label ??= $tag->getName();

        if (!$this->styleMap->hasStyle($label)) {
            $this->customTags->add($label, $tag);
        }
        return $this;
    }

    public function format(string|\Stringable $message): string
    {
        static $pattern = '#<([^>]*)>#';

        $message = (string)$message;

        $colors = Terminal::colorSupport() || Terminal::isColorForced();

        // fix escaped tags
        $message = str_replace(['\<', '\>'], ['&lt;', '&gt;'], $message);

        while (preg_match($pattern, $message, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $input = $matches[0][0];
            $labels = $matches[1][0];
            $len = strlen($input);

            $offset = (int)$matches[0][1];
            $this->buffer->write(substr($message, 0, $offset));
            $message = substr($message, $offset + $len);

            if (str_starts_with($labels, '/')) {
                if ($colors) {
                    $this->buffer->write(Ansi::RESET);
                }
                continue;
            }

            $attributes = preg_split('#\h+#', trim($labels));
            $buffer = [];

            foreach ($attributes as $attribute) {
                /** @var CustomTag $plugin */
                if ($plugin = $this->customTags->get($attribute)) {
                    $contents = '';
                    $buffer = null;
                    $plugin->setAttributes($attributes)->setSupportsColor($colors);

                    if (
                        $plugin->usesContents()
                        && preg_match($pattern, $message, $matches, PREG_OFFSET_CAPTURE)
                        && str_starts_with(trim($matches[1][0]), '/')
                    ) {
                        $len = strlen($matches[0][0]);
                        $offset = (int)$matches[0][1];
                        $contents = substr($message, 0, $offset);
                        $message = substr($message, $offset + $len);
                    }

                    $str = str_val($plugin->format($contents));
                    $plugin->setAttributes([]);

                    $message = $str . $message;
                    break;
                }

                if ($colors && $style = $this->styleMap->getStyle($attribute)) {
                    $buffer[] = $style->getAnsiString();
                }
            }

            if ($buffer) {
                $this->buffer->write(...$buffer);
            }
        }

        $cnt = count($this->buffer);
        $message = $this->buffer->pullString() . $message;

        if ($cnt && $colors) {
            $message .= Ansi::RESET;
        }

        return str_replace(['&gt;', '&lt;'], ['>', '<'], $message);
    }
}
