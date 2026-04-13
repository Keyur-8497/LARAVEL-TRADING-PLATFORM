<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use KiteConnect\KiteConnect;

class StockController extends Controller
{
    /**
     * Watchlist items with both the websocket token and the quote API key.
     */
    private const INSTRUMENTS = [
        [
            'token' => 408065,
            'symbol' => 'INFY',
            'quote_key' => 'NSE:INFY',
        ],
        [
            'token' => 738561,
            'symbol' => 'RELIANCE',
            'quote_key' => 'NSE:RELIANCE',
        ],
        [
            'token' => 2953217,
            'symbol' => 'TCS',
            'quote_key' => 'NSE:TCS',
        ],
        [
            'token' => 341249,
            'symbol' => 'HDFCBANK',
            'quote_key' => 'NSE:HDFCBANK',
        ],
        [
            'token' => 1270529,
            'symbol' => 'ICICIBANK',
            'quote_key' => 'NSE:ICICIBANK',
        ],
        [
            'token' => 895745,
            'symbol' => 'TATASTEEL',
            'quote_key' => 'NSE:TATASTEEL',
        ],
        [
            'token' => 884737,
            'symbol' => 'TATAMOTORS',
            'quote_key' => 'NSE:TATAMOTORS',
        ],
        [
            'token' => 779521,
            'symbol' => 'SBIN',
            'quote_key' => 'NSE:SBIN',
        ],
        [
            'token' => 256265,
            'symbol' => 'NIFTY 50',
            'quote_key' => 'NSE:NIFTY 50',
        ],
    ];

    /**
     * Display the live stock prices.
     */
    public function index(KiteConnect $kite)
    {
        try {
            $quotes = $kite->getQuote(array_column(self::INSTRUMENTS, 'quote_key'));

            $stockData = [];
            foreach (self::INSTRUMENTS as $instrument) {
                $quote = $quotes[$instrument['quote_key']] ?? null;
                $lastPrice = $quote->last_price ?? 0;
                $closePrice = $quote->ohlc->close ?? 0;
                $changePercent = $closePrice > 0
                    ? round((($lastPrice - $closePrice) * 100) / $closePrice, 2)
                    : 0;

                $stockData[] = [
                    'symbol' => $instrument['symbol'],
                    'price' => $lastPrice,
                    'change' => $changePercent,
                    'is_positive' => $changePercent >= 0,
                    'token' => $instrument['token'],
                ];
            }

            return view('stocks', compact('stockData'));
        } catch (\Throwable $e) {
            Log::error('Kite API Error: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return view('stocks', [
                'error' => 'API Error: '.$e->getMessage().'. Please check if your access token is valid.',
                'stockData' => [],
            ]);
        }
    }
}
