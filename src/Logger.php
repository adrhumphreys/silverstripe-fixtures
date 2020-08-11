<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

class Logger
{
    private const COLOR_DEFAULT = '35';
    private const COLOR_GREEN = '32';
    private const COLOR_ORANGE = '33';
    private const COLOR_BLUE = '36';

    public static function blue(string $message, bool $italics = false, $bold = false): void
    {
        self::log($message, self::COLOR_BLUE, $italics, $bold);
    }

    public static function green(string $message, bool $italics = false, $bold = true): void
    {
        self::log($message, self::COLOR_GREEN, $italics, $bold);
    }

    public static function orange(string $message, bool $italics = true, $bold = false): void
    {
        self::log($message, self::COLOR_ORANGE, $italics, $bold);
    }

    public static function log(
        string $message,
        string $color = self::COLOR_DEFAULT,
        bool $italics = false,
        bool $bold = false
    ): void {
        $string = sprintf("\033[%sm %s \033[0m %s", $color, $message, PHP_EOL);

        if ($italics) {
            $string = sprintf("\e[3m%s\e[0m", $string);
        }

        if ($bold) {
            $string = sprintf("\e[1m%s\e[0m", $string);
        }

        echo $string;
    }
}
