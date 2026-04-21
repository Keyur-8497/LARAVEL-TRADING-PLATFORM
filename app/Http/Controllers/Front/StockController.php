<?php

namespace App\Http\Controllers\Front;

use App\Services\KiteSessionManager;
use App\Support\ApplicationLogger;
use App\Support\TradingInstrumentRegistry;
use KiteConnect\KiteConnect;

class StockController extends FrontMainController
{
    /**
     * Paper trading sandbox — no live API call, fully static prices.
     */
    public function paperTrading(): \Illuminate\View\View
    {
        $stockData = array_map(fn ($instrument) => [
            'symbol'      => $instrument['symbol'],
            'price'       => $instrument['paper_price'] ?? 1000.00,
            'change'      => 0.00,
            'is_positive' => true,
            'token'       => $instrument['token'],
        ], TradingInstrumentRegistry::watchlist());

        return view('Front.paper-trading', compact('stockData'));
    }

    /**
     * Display the live stock prices.
     */
    public function index(KiteConnect $kite, KiteSessionManager $kiteSessionManager)
    {
        $sessionData = $kiteSessionManager->getSessionData();

        if (! $sessionData || ! filled($kiteSessionManager->getAccessToken())) {
            ApplicationLogger::warning('Dashboard access redirected because Zerodha session is missing.', [
                'route' => 'dashboard',
            ]);

            return redirect()
                ->route('home')
                ->with('error', 'Connect Zerodha first to open the live dashboard.');
        }

        try {
            $watchlist = TradingInstrumentRegistry::watchlist();
            $quotes = $kite->getQuote(array_column($watchlist, 'quote_key'));
            $tokenMap = $this->resolveInstrumentTokens($kite, $watchlist);

            $stockData = [];
            foreach ($watchlist as $instrument) {
                $quote = $quotes[$instrument['quote_key']] ?? null;
                $lastPrice = $quote->last_price ?? 0;
                $closePrice = $quote->ohlc->close ?? 0;
                $changePercent = $closePrice > 0
                    ? round((($lastPrice - $closePrice) * 100) / $closePrice, 2)
                    : 0;

                $stockData[] = [
                    'symbol' => $instrument['symbol'],
                    'price' => $lastPrice,
                    'close' => $closePrice,
                    'change' => $changePercent,
                    'is_positive' => $changePercent >= 0,
                    'token' => $instrument['token'] ?? ($tokenMap[$instrument['tradingsymbol']] ?? null),
                ];
            }

            return view('Front.stocks', [
                'stockData' => $stockData,
                'apiKey' => config('kite.api_key'),
                'accessToken' => $kiteSessionManager->getAccessToken(),
                'sessionData' => $sessionData,
            ]);
        } catch (\Throwable $e) {
            ApplicationLogger::error(
                'Live dashboard quote loading failed.',
                ApplicationLogger::exceptionContext($e, [
                    'route' => 'dashboard',
                ])
            );

            return view('Front.stocks', [
                'error' => 'Live mode could not connect to Zerodha: '.$e->getMessage().'. If today\'s token has expired, reconnect from the home page.',
                'stockData' => [],
                'apiKey' => config('kite.api_key'),
                'accessToken' => $kiteSessionManager->getAccessToken(),
                'sessionData' => $sessionData,
            ]);
        }
    }

    private function resolveInstrumentTokens(KiteConnect $kite, array $watchlist): array
    {
        $missingSymbols = array_values(array_filter(array_map(
            fn ($instrument) => empty($instrument['token']) ? $instrument['tradingsymbol'] : null,
            $watchlist
        )));

        if ($missingSymbols === []) {
            return [];
        }

        $instruments = $kite->getInstruments('NSE');
        $tokens = [];

        foreach ($instruments as $instrument) {
            if (in_array($instrument->tradingsymbol, $missingSymbols, true)) {
                $tokens[$instrument->tradingsymbol] = $instrument->instrument_token;
            }
        }

        return $tokens;
    }
}
