<?php

namespace App\Support;

class TradingInstrumentRegistry
{
    public static function all(): array
    {
        return [
            'TATSILV' => [
                'symbol' => 'TATSILV',
                'exchange' => 'NSE',
                'tradingsymbol' => 'TATSILV',
                'quote_key' => 'NSE:TATSILV',
                'token' => null,
                'tradable' => true,
                'paper_price' => 24.00,
            ],
            'RELIANCE' => [
                'symbol' => 'RELIANCE',
                'exchange' => 'NSE',
                'tradingsymbol' => 'RELIANCE',
                'quote_key' => 'NSE:RELIANCE',
                'token' => 738561,
                'tradable' => true,
                'paper_price' => 1250.00,
            ],
            'HDFCBANK' => [
                'symbol' => 'HDFCBANK',
                'exchange' => 'NSE',
                'tradingsymbol' => 'HDFCBANK',
                'quote_key' => 'NSE:HDFCBANK',
                'token' => 341249,
                'tradable' => true,
                'paper_price' => 1720.00,
            ],
            'ICICIBANK' => [
                'symbol' => 'ICICIBANK',
                'exchange' => 'NSE',
                'tradingsymbol' => 'ICICIBANK',
                'quote_key' => 'NSE:ICICIBANK',
                'token' => 1270529,
                'tradable' => true,
                'paper_price' => 1370.00,
            ],
            'SBIN' => [
                'symbol' => 'SBIN',
                'exchange' => 'NSE',
                'tradingsymbol' => 'SBIN',
                'quote_key' => 'NSE:SBIN',
                'token' => 779521,
                'tradable' => true,
                'paper_price' => 812.00,
            ],
        ];
    }

    public static function watchlist(): array
    {
        return array_values(self::all());
    }

    public static function symbols(): array
    {
        return array_keys(self::all());
    }

    public static function get(string $symbol): ?array
    {
        return self::all()[$symbol] ?? null;
    }
}
