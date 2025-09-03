<?php
declare(strict_types=1);

namespace App\Utils;

final class Log
{
    private const LEVEL_ORDER = [
        'silent' => -1,
        'error' => 0,
        'warn'  => 1,
        'info'  => 2,
        'debug' => 3,
    ];

    private static string $level = 'info';
    private static ?string $requestId = null;
    private static array $counters = [];

    public static function setLevel(string $level): void
    {
        $level = strtolower($level);
        if (!isset(self::LEVEL_ORDER[$level])) {
            $level = 'info';
        }
        self::$level = $level;
    }

    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    public static function warn(string $event, array $context = []): void
    {
        self::write('warn', $event, $context);
    }

    public static function error(string $event, array $context = []): void
    {
        self::write('error', $event, $context);
    }

    public static function debug(string $event, array $context = []): void
    {
        self::write('debug', $event, $context);
    }

    public static function setRequestId(string $requestId): void
    {
        self::$requestId = $requestId;
    }

    public static function incCounter(string $name, int $delta = 1): void
    {
        if (!isset(self::$counters[$name])) {
            self::$counters[$name] = 0;
        }
        self::$counters[$name] += $delta;
    }

    public static function getCounters(): array
    {
        return self::$counters;
    }

    private static function write(string $level, string $event, array $context): void
    {
        if (self::LEVEL_ORDER[$level] > self::LEVEL_ORDER[self::$level]) {
            return;
        }

        $record = [
            'ts'     => date('c'),
            'level'  => $level,
            'event'  => $event,
            'ctx'    => $context,
        ];

        if (self::$requestId !== null) {
            $record['rid'] = self::$requestId;
        }


        $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {

            $stream = (getenv('APP_ENV') === 'testing') ? 'php://stderr' : 'php://stdout';

            file_put_contents($stream, $json . "\n");
        }
    }
}


