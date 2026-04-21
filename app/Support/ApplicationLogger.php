<?php

namespace App\Support;

use Illuminate\Http\Request;
use Throwable;

class ApplicationLogger
{
    public static function request(string $message, array $context = []): void
    {
        self::write('requests', 'info', $message, $context);
    }

    public static function event(string $message, array $context = []): void
    {
        self::write('events', 'info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('errors', 'error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('errors', 'warning', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('events', 'info', $message, $context);
    }

    public static function write(string $fileName, string $level, string $message, array $context = []): void
    {
        $directory = storage_path('logs/application-tracking/'.now()->format('Y-m-d'));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.'/'.$fileName.'.log';
        $content = self::formatLogBlock($fileName, strtoupper($level), $message, self::normalizeContext($context));

        file_put_contents($path, $content, FILE_APPEND);
    }

    public static function requestContext(Request $request): array
    {
        return [
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route_name' => optional($request->route())->getName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'query' => self::sanitize($request->query()),
            'payload' => self::sanitize($request->except(['_token'])),
        ];
    }

    public static function exceptionContext(Throwable $exception, array $context = []): array
    {
        return array_merge($context, [
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    private static function normalizeContext(array $context): array
    {
        return self::sanitize($context);
    }

    private static function formatLogBlock(string $fileName, string $level, string $message, array $context): string
    {
        $title = match ($fileName) {
            'requests' => str_contains(strtoupper($message), 'RESPONSE') ? 'REQUEST END' : 'REQUEST START',
            'errors' => $level,
            default => 'EVENT',
        };

        $lines = [];
        $lines[] = str_repeat('=', 20).' '.$title.' '.str_repeat('=', 20);
        $lines[] = 'Time        : '.now()->format('Y-m-d H:i:s');
        $lines[] = 'Level       : '.$level;
        $lines[] = 'Message     : '.$message;

        foreach ($context as $key => $value) {
            $label = self::labelize((string) $key);
            $lines[] = str_pad($label, 12, ' ', STR_PAD_RIGHT).' : '.self::stringify($value);
        }

        $lines[] = str_repeat('=', 56);
        $lines[] = '';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private static function labelize(string $key): string
    {
        $label = str_replace(['_', '-'], ' ', $key);
        $label = ucwords($label);

        return mb_strimwidth($label, 0, 12, '');
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? $encoded : '[unserializable value]';
    }

    private static function sanitize(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            return [
                'exception_class' => $value::class,
                'message' => $value->getMessage(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ];
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $lowerKey = is_string($key) ? strtolower($key) : $key;

                if (is_string($lowerKey) && in_array($lowerKey, [
                    'password',
                    'password_confirmation',
                    'api_secret',
                    'access_token',
                    'request_token',
                    'checksum',
                    'authorization',
                ], true)) {
                    $sanitized[$key] = '[FILTERED]';
                    continue;
                }

                $sanitized[$key] = self::sanitize($item);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return self::sanitize(json_decode(json_encode($value), true) ?: ['value' => (string) $value]);
        }

        return $value;
    }
}
