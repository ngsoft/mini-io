<?php

declare(strict_types=1);

namespace NGSOFT\IO;

class Input
{
    /**
     * @var resource
     */
    protected $stream;

    public function __construct()
    {
        $this->stream = fopen('php://input', 'r+');
    }

    public function __destruct()
    {
        if ($this->stream)
        {
            fclose($this->stream);
        }
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    public function readLn(bool $allowEmpty = true): string
    {
        $cp     = 0;
        $result = false;

        if (function_exists('sapi_windows_cp_set'))
        {
            $cp = sapi_windows_cp_get();
            sapi_windows_cp_set(sapi_windows_cp_get('oem'));
        }

        while (false === $result)
        {
            $line   = @fgets($this->stream, 4096);
            $line   = rtrim($line, "\r\n");

            if (empty($line) && ! $allowEmpty)
            {
                continue;
            }
            $result = empty($line) ? '' : $line;
        }

        if (0 !== $cp)
        {
            sapi_windows_cp_set($cp);

            if ( ! empty($result))
            {
                $result = sapi_windows_cp_conv(sapi_windows_cp_get('oem'), $cp, $result) ?? false;
            }
        }

        return $result;
    }

    /**
     * Read lines from the input.
     *
     * @return string[]
     */
    public function read(int $lines = 1, bool $allowEmptyLines = true): array
    {
        $result = [];

        while (count($result) < $lines)
        {
            $result[] = $this->readln($allowEmptyLines);
        }

        return $result;
    }
}
