<?php

namespace NGSOFT\IO;

abstract class Terminal
{
    protected static bool $colorForced = false;

    public static function forceColors(): void
    {
        self::$colorForced = true;
    }

    public static function isColorForced(): bool
    {
        return self::$colorForced;
    }

    /**
     * Get Terminal size.
     *
     * @return int[] list($width, $height)
     */
    public static function getSize(): array
    {
        $width  = 80;
        $height = 25;

        if (DIRECTORY_SEPARATOR === '\\')
        {
            if (self::supportsPowershell())
            {
                $width  = trim(shell_exec('powershell.exe $Host.UI.RawUI.WindowSize.Width;') ?? '80');
                $height = trim(shell_exec('powershell.exe $Host.UI.RawUI.WindowSize.Height') ?? '25');
            } elseif ($out = self::executeProcess('mode.com con /status'))
            {
                list($height, $width) = array_map(fn ($arr) => $arr[1], preg_exec('#(\d+)#', $out, 2));
            }
        } elseif (self::ttySupport() && $out = self::executeProcess('stty size'))
        {
            list($height, $width) = array_map(fn ($arr) => $arr[1], preg_exec('#(\d+)#', $out, 2));
        }
        return [(int) $width, (int) $height];
    }

    /**
     * Get terminal width.
     */
    public static function getWidth(): int
    {
        return self::getSize()[0];
    }

    /**
     * Get terminal height.
     */
    public static function getHeight(): int
    {
        return self::getSize()[1];
    }

    public static function executeProcess(string $command): ?string
    {
        if ( ! function_exists('proc_open'))
        {
            return null;
        }

        try
        {
            set_default_error_handler();

            $process = @proc_open(
                $command,
                [
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
                null,
                null,
                ['suppress_errors' => true]
            );

            if ( ! $process)
            {
                return null;
            }

            $result  = stream_get_contents($pipes[1]);

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return $result;
        } catch (\Throwable)
        {
            return null;
        } finally
        {
            restore_error_handler();
        }
    }

    /**
     * Get Number of colors supported.
     */
    public static function getNumColorSupport(): int
    {
        static $result;

        if (is_null($result))
        {
            $result = 8;

            if ('truecolor' === getenv('COLORTERM'))
            {
                $result = 16777215;
            } elseif (self::colorSupport())
            {
                $result = 256;
            } elseif ($value = self::executeProcess('tput colors'))
            {
                $result = intval($value);
            }
        }

        return $result;
    }

    public static function supportsPowershell(): bool
    {
        static $result;
        return $result ??= DIRECTORY_SEPARATOR === '\\' && ! empty(self::executeProcess('powershell.exe -?'));
    }

    public static function colorSupport(): bool
    {
        static $result;

        if (is_null($result))
        {
            if (
                isset($_SERVER['NO_COLOR'])
                || false !== getenv('NO_COLOR')
            ) {
                return $result = false;
            }

            if (getenv('TERM_PROGRAM'))
            {
                return $result = true;
            }

            try
            {
                set_default_error_handler();

                $stream        = fopen('php://stdout', 'w');

                if (DIRECTORY_SEPARATOR === '\\')
                {
                    if (function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support($stream))
                    {
                        return $result = true;
                    }

                    return $result = preg_test('#^(cygwin|xterm)#i', getenv('TERM') ?: '')
                        || false !== getenv('ANSICON')
                        || 'ON' === getenv('ConEmuANSI');
                }

                if (function_exists('stream_isatty'))
                {
                    return $result = stream_isatty($stream);
                }

                if (function_exists('posix_isatty'))
                {
                    return $result = posix_isatty($stream);
                }
                $stat          = fstat($stream);
                // Check if formatted mode is S_IFCHR
                return $result = $stat && 0020000 === ($stat['mode'] & 0170000);
            } catch (\Throwable)
            {
                return $result = false;
            } finally
            {
                isset($stream) && fclose($stream);
            }
        }

        return $result;
    }

    public static function ttySupport(): bool
    {
        static $supported;

        if (is_null($supported))
        {
            $supported = false;

            if (function_exists('proc_open'))
            {
                try
                {
                    set_default_error_handler();
                    $supported = (bool) proc_open(
                        'echo 1 >/dev/null',
                        [
                            ['file', '/dev/tty', 'r'],
                            ['file', '/dev/tty', 'w'],
                            ['file', '/dev/tty', 'w'],
                        ],
                        $pipes,
                        null,
                        null,
                        ['suppress_errors' => true]
                    );
                } catch (\Throwable)
                {
                } finally
                {
                    restore_error_handler();
                }
            }
        }

        return $supported;
    }

    /**
     * Removes the styles escapes characters from a message.
     *
     * @noinspection RegExpDuplicateAlternationBranch
     * @noinspection RegExpSingleCharAlternation
     */
    public static function removeStyling(string|\Stringable $message): string
    {
        return preg_replace('#(?:\x1b|\033)\[[^m]+m#i', '', (string) $message);
    }
}
