<?php

namespace App\Support;

class TradingErrorLogger
{
    public static function write(string $level, string $message, array $context = []): void
    {
        $fileName = in_array(strtolower($level), ['error', 'warning', 'critical', 'alert', 'emergency'], true)
            ? 'errors'
            : 'events';

        ApplicationLogger::write($fileName, $level, $message, array_merge($context, [
            'log_scope' => 'trading',
        ]));
    }
}
