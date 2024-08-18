<?php

declare(strict_types=1);

namespace NGSOFT\IO;

use NGSOFT\DataStructure\Collection;

class GetOpt extends Collection
{
    protected array $counts = [];

    /**
     * Resets the storage and parse arguments
     * if arguments is not set, parse the global arguments.
     */
    public function parseArguments(?array $arguments = null): static
    {
        static $argToken = '#^(-{1,2})(\w.+)#', $equalsToken = '#^(.+)=(.+)$#', $reNegIntFloat = '#^-(?:[.,]?\d+|\d+[.,]\d+)$#';

        $this->unlock();
        $this->clear();
        $this->counts    = [];

        $arguments ??= $_SERVER['argv'];

        $long            = false;
        $current         = null;
        $index           = -1;

        while (count($arguments))
        {
            $arg = array_shift($arguments);

            if (preg_test($reNegIntFloat, $arg))
            {
                $arg = str_replace(',', '.', $arg);
            } elseif (preg_match($argToken, $arg, $matches))
            {
                if ($current)
                {
                    $this->addArgument($current, 'true', $long);
                }

                list(, $token, $current) = $matches;
                $long                    = '--' === $token;

                if (preg_match($equalsToken, $current, $matches))
                {
                    list(, $current, $value) = $matches;
                    $this->addArgument($current, $value, $long);
                    $current                 = null;
                    $long                    = false;
                    continue;
                }

                // last arg
                if ( ! count($arguments))
                {
                    $this->addArgument($current, 'true', $long);
                }

                continue;
            }
            // $arg is a value
            $this->addArgument($current ?? ++$index, $arg, $long);
        }

        $this->lock();
        return $this;
    }

    /**
     * Get Boolean argument.
     */
    public function getBoolean(string $name, bool $defaultValue = false): bool
    {
        $val = $this->offsetGet($name) ?? $defaultValue;

        return match ($val)
        {
            true, 'y', 'yes', 'true', '1', 'on' => true,
            false, 'n', 'no', 'false', '0', 'off' => false,
            default => (bool) $val
        };
    }

    /**
     * Get int argument.
     */
    public function getInt(string $name, int $defaultValue = 0): int
    {
        $val = $this->offsetGet($name) ?? $defaultValue;

        if (is_numeric($val))
        {
            return intval($val);
        }
        return $defaultValue;
    }

    /**
     * Get Float argument.
     */
    public function getFloat(string $name, float $defaultValue = 0.0): float
    {
        $val = $this->offsetGet($name) ?? $defaultValue;

        if (is_numeric($val))
        {
            return floatval($val);
        }
        return $defaultValue;
    }

    /**
     * Get String argument.
     */
    public function getString(string $name, string $defaultValue = ''): string
    {
        return $this->offsetGet($name) ?? $defaultValue;
    }

    /**
     * Get values without arguments.
     */
    public function getValues(): array
    {
        return $this->filter(fn ($_, $n) => is_int($n))->toArray();
    }

    /**
     * Get all parsed arguments.
     */
    public function getArguments(): array
    {
        return $this->filter(fn ($_, $n) => ! is_int($n))->toArray();
    }

    /**
     * Get number of occurrences of a single argument.
     */
    public function getArgumentCount(string $name): int
    {
        return $this->counts[$name] ?? 0;
    }

    protected function addArgument(int|string $name, mixed $value, bool $long): void
    {
        if ( ! $long && is_string($name))
        {
            $name = mb_str_split($name);
        }

        if ( ! is_array($name))
        {
            $name = [$name];
        }

        foreach ($name as $key)
        {
            if (is_string($key))
            {
                if (is_numeric($key))
                {
                    $key = "#{$key}";
                }

                $this->counts[$key] ??= 0;
                ++$this->counts[$key];
            }
            $this->append($key, $value);
        }
    }
}
