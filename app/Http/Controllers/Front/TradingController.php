<?php

namespace App\Http\Controllers\Front;

use App\Models\TradeStrategy;
use App\Models\TradeStrategyLevel;
use App\Services\KiteSessionManager;
use App\Support\ApplicationLogger;
use App\Support\TradingErrorLogger;
use App\Support\TradingInstrumentRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use KiteConnect\KiteConnect;

class TradingController extends FrontMainController
{
    protected KiteSessionManager $kiteSessionManager;
    protected TradeStrategy $TradeStrategy;
    protected TradeStrategyLevel $TradeStrategyLevel;

    public function __construct(KiteSessionManager $kiteSessionManager)
    {
        parent::__construct();
        $this->kiteSessionManager = $kiteSessionManager;
        $this->TradeStrategy = new TradeStrategy;
        $this->TradeStrategyLevel = new TradeStrategyLevel;
    }

    public function CreateStrategy(Request $request): JsonResponse
    {
        $input = $request->validate([
            'symbol' => ['required', 'string', Rule::in(TradingInstrumentRegistry::symbols())],
            'base_price' => ['required', 'numeric', 'gt:0'],
            'buy_offset' => ['required', 'numeric', 'gt:0'],
            'sell_offset' => ['required', 'numeric', 'gt:0'],
            'lot_size' => ['required', 'integer', 'min:1'],
            'lots_limit' => ['required', 'integer', 'min:1', 'max:5'],
            'capital_limit' => ['required', 'numeric', 'gt:0'],
        ]);

        ApplicationLogger::event('Create strategy request validated.', [
            'symbol' => $input['symbol'],
            'buy_offset' => $input['buy_offset'],
            'sell_offset' => $input['sell_offset'],
            'lot_size' => $input['lot_size'],
            'lots_limit' => $input['lots_limit'],
            'capital_limit' => $input['capital_limit'],
        ]);

        
        if (! $this->kiteSessionManager->hasActiveSession()) {
            ApplicationLogger::warning('Create strategy blocked because Zerodha session is inactive.', [
                'symbol' => $input['symbol'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Your Zerodha session expired. Please connect Zerodha again.',
            ], 401);
        }

        $instrument = TradingInstrumentRegistry::get($input['symbol']);
        
        if (! ($instrument['tradable'] ?? false)) {
            ApplicationLogger::warning('Create strategy blocked because symbol is not tradable.', [
                'symbol' => $input['symbol'],
                'instrument' => $instrument,
            ]);

            return response()->json([
                'success' => false,
                'message' => $input['symbol'].' is not configured for live trading orders.',
            ], 422);
        }

        $basePrice = round((float) $input['base_price'], 2);
        $buyOffset = round((float) $input['buy_offset'], 2);
        $sellOffset = round((float) $input['sell_offset'], 2);
        $lotSize = (int) $input['lot_size'];
        $lotsLimit = (int) $input['lots_limit'];
        $levels = $this->buildLevels($basePrice, $buyOffset, $sellOffset, $lotsLimit);
        $sessionData = $this->kiteSessionManager->getSessionData() ?? [];
        $kiteUserId = (string) ($sessionData['user_id'] ?? '');

        $kite = $this->kiteSessionManager->makeClient();

        try {
            $marketOrder = $this->toArray($kite->placeOrder(KiteConnect::VARIETY_REGULAR, [
                'tradingsymbol' => $instrument['tradingsymbol'],
                'exchange' => $instrument['exchange'],
                'quantity' => $lotSize,
                'transaction_type' => KiteConnect::TRANSACTION_TYPE_BUY,
                'order_type' => KiteConnect::ORDER_TYPE_MARKET,
                'product' => KiteConnect::PRODUCT_CNC,
                'validity' => KiteConnect::VALIDITY_DAY,
                'market_protection' => KiteConnect::MARKET_PROTECTION_AUTO,
            ]));
            $verifiedMarketOrder = $this->waitForExecutableMarketOrder(
                $kite,
                (string) ($marketOrder['order_id'] ?? '')
            );

            if (! ($verifiedMarketOrder['success'] ?? false)) {
                $this->logTradingError(
                    'Base market order verification failed.',
                    [
                        'symbol' => $input['symbol'],
                        'market_order_id' => $marketOrder['order_id'] ?? null,
                        'message' => $verifiedMarketOrder['message'] ?? null,
                        'order_step' => $verifiedMarketOrder['data'] ?? null,
                    ]
                );

                return response()->json([
                    'success' => false,
                    'message' => $verifiedMarketOrder['message'] ?? 'Base market order could not be verified.',
                ], 422);
            }
            $verifiedMarketOrderData = $verifiedMarketOrder['data'] ?? [];

            $sellTargetGtt = $this->toArray($kite->placeGTT([
                'trigger_type' => KiteConnect::GTT_TYPE_SINGLE,
                'tradingsymbol' => $instrument['tradingsymbol'],
                'exchange' => $instrument['exchange'],
                'trigger_values' => [$levels[0]['target_price']],
                'last_price' => $basePrice,
                'orders' => [[
                    'exchange' => $instrument['exchange'],
                    'tradingsymbol' => $instrument['tradingsymbol'],
                    'transaction_type' => KiteConnect::TRANSACTION_TYPE_SELL,
                    'quantity' => $lotSize,
                    'order_type' => KiteConnect::ORDER_TYPE_LIMIT,
                    'product' => KiteConnect::PRODUCT_CNC,
                    'price' => $levels[0]['target_price'],
                ]],
            ]));

            $buyGtts = [];
            foreach (array_slice($levels, 1) as $level) {
                $response = $kite->placeGTT([
                    'trigger_type' => KiteConnect::GTT_TYPE_SINGLE,
                    'tradingsymbol' => $instrument['tradingsymbol'],
                    'exchange' => $instrument['exchange'],
                    'trigger_values' => [$level['buy_price']],
                    'last_price' => $basePrice,
                    'orders' => [[
                        'exchange' => $instrument['exchange'],
                        'tradingsymbol' => $instrument['tradingsymbol'],
                        'transaction_type' => KiteConnect::TRANSACTION_TYPE_BUY,
                        'quantity' => $lotSize,
                        'order_type' => KiteConnect::ORDER_TYPE_LIMIT,
                        'product' => KiteConnect::PRODUCT_CNC,
                        'price' => $level['buy_price'],
                    ]],
                ]);

                $buyGtts[] = [
                    'level' => $level['level'],
                    'buy_price' => $level['buy_price'],
                    'target_price' => $level['target_price'],
                    'trigger_id' => $this->toArray($response)['trigger_id'] ?? null,
                ];
            }
            $tradeStrategyId = (int) RendomString(10, 'number');
            $strategyInputData = [];
            $strategyInputData['trade_strategy_id'] = $tradeStrategyId;
            $strategyInputData['kite_user_id'] = $kiteUserId;
            $strategyInputData['symbol'] = $input['symbol'];
            $strategyInputData['exchange'] = $instrument['exchange'];
            $strategyInputData['tradingsymbol'] = $instrument['tradingsymbol'];
            $strategyInputData['base_price'] = $basePrice;
            $strategyInputData['buy_offset'] = $buyOffset;
            $strategyInputData['sell_offset'] = $sellOffset;
            $strategyInputData['lot_size'] = $lotSize;
            $strategyInputData['lots_limit'] = $lotsLimit;
            $strategyInputData['capital_limit'] = (float) $input['capital_limit'];
            $strategyInputData['status'] = 'active';
            $strategyInputData['market_order_id'] = $verifiedMarketOrderData['order_id'] ?? null;
            $strategyInputData['market_order_status'] = $verifiedMarketOrderData['status'] ?? null;
            $strategyInputData['base_sell_gtt_trigger_id'] = $sellTargetGtt['trigger_id'] ?? null;
            $strategyInputData['total_realized_pnl'] = 0;
            $strategyInputData['total_unrealized_pnl'] = 0;
            $strategyInputData['started_at'] = now();
            $strategyInputData['completed_at'] = null;
            $strategyInputData['failure_reason'] = null;
            $strategyInputData['meta'] = [
                'market_order' => $verifiedMarketOrderData,
                'base_sell_gtt' => $sellTargetGtt,
                'buy_gtts' => $buyGtts,
            ];
            $strategyRecord = $this->TradeStrategy->InsertData($strategyInputData);

            $buyGttMap = [];
            foreach ($buyGtts as $buyGtt) {
                $buyGttMap[(int) $buyGtt['level']] = $buyGtt;
            }

            foreach ($levels as $level) {
                $isBaseLevel = (int) $level['level'] === 1;
                $buyGtt = $buyGttMap[(int) $level['level']] ?? null;

                $levelInputData = [];
                $levelInputData['trade_strategy_levels_id'] = (int) RendomString(10, 'number');
                $levelInputData['trade_strategy_id'] = $tradeStrategyId;
                $levelInputData['kite_user_id'] = $kiteUserId;
                $levelInputData['level_no'] = (int) $level['level'];
                $levelInputData['buy_price'] = (float) $level['buy_price'];
                $levelInputData['target_price'] = (float) $level['target_price'];
                $levelInputData['quantity'] = $lotSize;
                $levelInputData['status'] = $isBaseLevel ? 'sell_gtt_pending' : 'buy_gtt_pending';
                $levelInputData['buy_gtt_trigger_id'] = $isBaseLevel ? null : ($buyGtt['trigger_id'] ?? null);
                $levelInputData['buy_order_id'] = $isBaseLevel ? ($verifiedMarketOrderData['order_id'] ?? null) : null;
                $levelInputData['buy_order_status'] = $isBaseLevel ? ($verifiedMarketOrderData['status'] ?? null) : null;
                $levelInputData['buy_executed_price'] = $isBaseLevel ? (float) ($verifiedMarketOrderData['average_price'] ?? $basePrice) : null;
                $levelInputData['buy_executed_at'] = $isBaseLevel
                    ? ($this->normalizeDateTimeValue($verifiedMarketOrderData['exchange_timestamp'] ?? null)
                        ?? $this->normalizeDateTimeValue($verifiedMarketOrderData['order_timestamp'] ?? null)
                        ?? now())
                    : null;
                $levelInputData['sell_gtt_trigger_id'] = $isBaseLevel ? ($sellTargetGtt['trigger_id'] ?? null) : null;
                $levelInputData['sell_order_id'] = null;
                $levelInputData['sell_order_status'] = null;
                $levelInputData['sell_executed_price'] = null;
                $levelInputData['sell_executed_at'] = null;
                $levelInputData['realized_pnl'] = 0;
                $levelInputData['failure_reason'] = null;
                $levelInputData['meta'] = [
                    'is_base_level' => $isBaseLevel,
                    'buy_gtt' => $buyGtt,
                ];

                $this->TradeStrategyLevel->InsertData($levelInputData);
            }

            ApplicationLogger::event('Live strategy created successfully.', [
                'trade_strategy_id' => $strategyRecord->trade_strategy_id,
                'symbol' => $input['symbol'],
                'market_order_id' => $verifiedMarketOrderData['order_id'] ?? null,
                'base_sell_gtt_trigger_id' => $sellTargetGtt['trigger_id'] ?? null,
                'buy_gtt_count' => count($buyGtts),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Base market order placed and ladder GTTs created successfully.',
                'strategy' => [
                    'trade_strategy_id' => $strategyRecord->trade_strategy_id,
                    'symbol' => $input['symbol'],
                    'base_price' => $basePrice,
                    'buy_offset' => $buyOffset,
                    'sell_offset' => $sellOffset,
                    'lot_size' => $lotSize,
                    'lots_limit' => $lotsLimit,
                    'capital_limit' => (float) $input['capital_limit'],
                    'market_order_id' => $verifiedMarketOrderData['order_id'] ?? ($marketOrder['order_id'] ?? null),
                    'market_order_status' => $verifiedMarketOrderData['status'] ?? null,
                    'sell_target_trigger_id' => $sellTargetGtt['trigger_id'] ?? null,
                    'buy_gtts' => $buyGtts,
                ],
            ]);
        } catch (\Throwable $exception) {
            $this->logTradingError('Live strategy creation failed.', [
                'symbol' => $input['symbol'],
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create live strategy: '.$exception->getMessage(),
            ], 422);
        }
    }

    public function LotLadderData(Request $request): JsonResponse
    {
        $input = $request->validate([
            'symbol' => ['required', 'string', Rule::in(TradingInstrumentRegistry::symbols())],
        ]);

        if (! $this->kiteSessionManager->hasActiveSession()) {
            return response()->json([
                'success' => false,
                'message' => 'Your Zerodha session expired. Please connect Zerodha again.',
            ], 401);
        }

        $sessionData = $this->kiteSessionManager->getSessionData() ?? [];
        $kiteUserId = (string) ($sessionData['user_id'] ?? '');
        $strategy = $this->TradeStrategy
            ->newQuery()
            ->with(['levels' => function ($query) {
                $query->orderByDesc('buy_price');
            }])
            ->where('kite_user_id', $kiteUserId)
            ->where('symbol', $input['symbol'])
            ->where('status', 'active')
            ->orderByDesc('started_at')
            ->first();

        if (! $strategy) {
            return response()->json([
                'success' => true,
                'summary' => 'No strategy',
                'rows' => [],
            ]);
        }

        try {
            $kite = $this->kiteSessionManager->makeClient();
            $gtts = $this->toArray($kite->getGTTs());
            $orders = $this->toArray($kite->getOrders());
            $positions = $this->toArray($kite->getPositions());

            $symbolOrders = array_values(array_filter($orders, function ($order) use ($strategy) {
                return strtoupper((string) ($order['exchange'] ?? '')) === strtoupper((string) $strategy->exchange)
                    && strtoupper((string) ($order['tradingsymbol'] ?? '')) === strtoupper((string) $strategy->tradingsymbol);
            }));

            $netPositions = is_array($positions['net'] ?? null) ? $positions['net'] : [];
            $position = null;
            foreach ($netPositions as $item) {
                if (
                    strtoupper((string) ($item['exchange'] ?? '')) === strtoupper((string) $strategy->exchange)
                    && strtoupper((string) ($item['tradingsymbol'] ?? '')) === strtoupper((string) $strategy->tradingsymbol)
                ) {
                    $position = $item;
                    break;
                }
            }

            $gttMap = [];
            foreach ($gtts as $gtt) {
                $gttMap[(string) ($gtt['id'] ?? '')] = $gtt;
            }

            $rows = [];
            $summaryCounts = [
                'held' => 0,
                'pending' => 0,
                'open' => 0,
            ];

            foreach ($strategy->levels as $level) {
                $buyGtt = $gttMap[(string) ($level->buy_gtt_trigger_id ?? '')] ?? null;
                $sellGtt = $gttMap[(string) ($level->sell_gtt_trigger_id ?? '')] ?? null;
                $openOrder = $this->findOpenOrderForLevel($symbolOrders, $level);
                $status = $this->resolveLadderStatus($level, $buyGtt, $sellGtt, $openOrder, $position);

                if ($status === 'CLOSED') {
                    continue;
                }

                if ($status === 'HELD') {
                    $summaryCounts['held'] += 1;
                } elseif ($status === 'OPEN') {
                    $summaryCounts['open'] += 1;
                } else {
                    $summaryCounts['pending'] += 1;
                }

                $buyExecutedPrice = (float) ($level->buy_executed_price ?? $level->buy_price ?? 0);
                $currentPrice = (float) ($position['last_price'] ?? 0);
                $pnl = 0;

                if ($status === 'HELD' && $currentPrice > 0 && $buyExecutedPrice > 0) {
                    $pnl = ($currentPrice - $buyExecutedPrice) * (int) $level->quantity;
                }

                $rows[] = [
                    'level' => (int) $level->level_no,
                    'buy_price' => (float) $level->buy_price,
                    'target_price' => (float) $level->target_price,
                    'quantity' => (int) $level->quantity,
                    'status' => $status,
                    'pnl' => round($pnl, 2),
                    'source' => $openOrder ? 'order' : ($status === 'HELD' ? 'position' : 'gtt'),
                    'order_status' => $openOrder['status'] ?? null,
                    'gtt_status' => $sellGtt['status'] ?? $buyGtt['status'] ?? null,
                ];
            }

            usort($rows, function ($left, $right) {
                return ($right['buy_price'] <=> $left['buy_price']);
            });

            $summaryParts = [];
            if ($summaryCounts['held'] > 0) {
                $summaryParts[] = $summaryCounts['held'].'H';
            }
            if ($summaryCounts['open'] > 0) {
                $summaryParts[] = $summaryCounts['open'].'O';
            }
            if ($summaryCounts['pending'] > 0) {
                $summaryParts[] = $summaryCounts['pending'].'P';
            }

            return response()->json([
                'success' => true,
                'summary' => $strategy->symbol.' | '.($summaryParts !== [] ? implode(' / ', $summaryParts) : 'No activity'),
                'rows' => $rows,
            ]);
        } catch (\Throwable $exception) {
            $this->logTradingError('Unable to fetch lot ladder data.', [
                'symbol' => $input['symbol'],
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load lot ladder data: '.$exception->getMessage(),
            ], 422);
        }
    }

    public function PositionsData(Request $request): JsonResponse
    {
        $input = $request->validate([
            'symbol' => ['required', 'string', Rule::in(TradingInstrumentRegistry::symbols())],
        ]);

        if (! $this->kiteSessionManager->hasActiveSession()) {
            return response()->json([
                'success' => false,
                'message' => 'Your Zerodha session expired. Please connect Zerodha again.',
            ], 401);
        }

        $instrument = TradingInstrumentRegistry::get($input['symbol']);

        try {
            $kite = $this->kiteSessionManager->makeClient();
            $positions = $this->toArray($kite->getPositions());
            $netPositions = is_array($positions['net'] ?? null) ? $positions['net'] : [];
            $rows = [];

            foreach ($netPositions as $position) {
                if (
                    strtoupper((string) ($position['exchange'] ?? '')) !== strtoupper((string) ($instrument['exchange'] ?? ''))
                    || strtoupper((string) ($position['tradingsymbol'] ?? '')) !== strtoupper((string) ($instrument['tradingsymbol'] ?? ''))
                    || (int) ($position['quantity'] ?? 0) === 0
                ) {
                    continue;
                }

                $rows[] = [
                    'symbol' => $input['symbol'],
                    'tradingsymbol' => $position['tradingsymbol'] ?? $input['symbol'],
                    'exchange' => $position['exchange'] ?? ($instrument['exchange'] ?? ''),
                    'product' => $position['product'] ?? '',
                    'quantity' => (int) ($position['quantity'] ?? 0),
                    'average_price' => round((float) ($position['average_price'] ?? 0), 2),
                    'last_price' => round((float) ($position['last_price'] ?? 0), 2),
                    'buy_quantity' => (int) ($position['buy_quantity'] ?? 0),
                    'sell_quantity' => (int) ($position['sell_quantity'] ?? 0),
                    'pnl' => round((float) ($position['pnl'] ?? 0), 2),
                    'unrealised' => round((float) ($position['unrealised'] ?? 0), 2),
                    'realised' => round((float) ($position['realised'] ?? 0), 2),
                ];
            }

            return response()->json([
                'success' => true,
                'symbol' => $input['symbol'],
                'positions' => $rows,
            ]);
        } catch (\Throwable $exception) {
            $this->logTradingError('Unable to fetch positions data.', [
                'symbol' => $input['symbol'],
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load positions data: '.$exception->getMessage(),
            ], 422);
        }
    }

    public function SymbolData(Request $request): JsonResponse
    {
        $input = $request->validate([
            'symbol' => ['required', 'string', Rule::in(TradingInstrumentRegistry::symbols())],
        ]);

        if (! $this->kiteSessionManager->hasActiveSession()) {
            return response()->json([
                'success' => false,
                'message' => 'Your Zerodha session expired. Please connect Zerodha again.',
            ], 401);
        }

        $instrument = TradingInstrumentRegistry::get($input['symbol']);
        $sessionData = $this->kiteSessionManager->getSessionData() ?? [];
        $kiteUserId = (string) ($sessionData['user_id'] ?? '');

        try {
            $kite = $this->kiteSessionManager->makeClient();
            $gtts = $this->toArray($kite->getGTTs());
            $orders = $this->toArray($kite->getOrders());
            $positions = $this->toArray($kite->getPositions());
            $positionsPayload = $this->buildPositionsRows($input['symbol'], $instrument, $positions);
            $closedTradesPayload = $this->buildClosedTradesRows($kiteUserId, $input['symbol']);

            $strategy = $this->TradeStrategy
                ->newQuery()
                ->with(['levels' => function ($query) {
                    $query->orderByDesc('buy_price');
                }])
                ->where('kite_user_id', $kiteUserId)
                ->where('symbol', $input['symbol'])
                ->where('status', 'active')
                ->orderByDesc('started_at')
                ->first();

            if (! $strategy) {
                return response()->json([
                    'success' => true,
                    'symbol' => $input['symbol'],
                    'has_active_strategy' => false,
                    'strategy' => null,
                    'lot_ladder' => [
                        'summary' => 'No strategy',
                        'rows' => [],
                    ],
                    'positions' => $positionsPayload,
                    'closed_trades' => $closedTradesPayload,
                ]);
            }

            $symbolOrders = array_values(array_filter($orders, function ($order) use ($strategy) {
                return strtoupper((string) ($order['exchange'] ?? '')) === strtoupper((string) $strategy->exchange)
                    && strtoupper((string) ($order['tradingsymbol'] ?? '')) === strtoupper((string) $strategy->tradingsymbol);
            }));

            $netPositions = is_array($positions['net'] ?? null) ? $positions['net'] : [];
            $position = null;
            foreach ($netPositions as $item) {
                if (
                    strtoupper((string) ($item['exchange'] ?? '')) === strtoupper((string) $strategy->exchange)
                    && strtoupper((string) ($item['tradingsymbol'] ?? '')) === strtoupper((string) $strategy->tradingsymbol)
                ) {
                    $position = $item;
                    break;
                }
            }

            $gttMap = [];
            foreach ($gtts as $gtt) {
                $gttMap[(string) ($gtt['id'] ?? '')] = $gtt;
            }

            $rows = [];
            $summaryCounts = [
                'held' => 0,
                'pending' => 0,
                'open' => 0,
            ];

            foreach ($strategy->levels as $level) {
                $buyGtt = $gttMap[(string) ($level->buy_gtt_trigger_id ?? '')] ?? null;
                $sellGtt = $gttMap[(string) ($level->sell_gtt_trigger_id ?? '')] ?? null;
                $openOrder = $this->findOpenOrderForLevel($symbolOrders, $level);
                $status = $this->resolveLadderStatus($level, $buyGtt, $sellGtt, $openOrder, $position);

                if ($status === 'CLOSED') {
                    continue;
                }

                if ($status === 'HELD') {
                    $summaryCounts['held'] += 1;
                } elseif ($status === 'OPEN') {
                    $summaryCounts['open'] += 1;
                } else {
                    $summaryCounts['pending'] += 1;
                }

                $buyExecutedPrice = (float) ($level->buy_executed_price ?? $level->buy_price ?? 0);
                $currentPrice = (float) ($position['last_price'] ?? 0);
                $pnl = 0;

                if ($status === 'HELD' && $currentPrice > 0 && $buyExecutedPrice > 0) {
                    $pnl = ($currentPrice - $buyExecutedPrice) * (int) $level->quantity;
                }

                $rows[] = [
                    'level' => (int) $level->level_no,
                    'buy_price' => (float) $level->buy_price,
                    'target_price' => (float) $level->target_price,
                    'quantity' => (int) $level->quantity,
                    'status' => $status,
                    'pnl' => round($pnl, 2),
                    'source' => $openOrder ? 'order' : ($status === 'HELD' ? 'position' : 'gtt'),
                    'order_status' => $openOrder['status'] ?? null,
                    'gtt_status' => $sellGtt['status'] ?? $buyGtt['status'] ?? null,
                ];
            }

            usort($rows, function ($left, $right) {
                return ($right['buy_price'] <=> $left['buy_price']);
            });

            $summaryParts = [];
            if ($summaryCounts['held'] > 0) {
                $summaryParts[] = $summaryCounts['held'].'H';
            }
            if ($summaryCounts['open'] > 0) {
                $summaryParts[] = $summaryCounts['open'].'O';
            }
            if ($summaryCounts['pending'] > 0) {
                $summaryParts[] = $summaryCounts['pending'].'P';
            }

            return response()->json([
                'success' => true,
                'symbol' => $input['symbol'],
                'has_active_strategy' => true,
                'strategy' => [
                    'trade_strategy_id' => $strategy->trade_strategy_id,
                    'symbol' => $strategy->symbol,
                    'exchange' => $strategy->exchange,
                    'tradingsymbol' => $strategy->tradingsymbol,
                    'base_price' => round((float) $strategy->base_price, 2),
                    'buy_offset' => round((float) $strategy->buy_offset, 2),
                    'sell_offset' => round((float) $strategy->sell_offset, 2),
                    'lot_size' => (int) $strategy->lot_size,
                    'lots_limit' => (int) $strategy->lots_limit,
                    'capital_limit' => round((float) $strategy->capital_limit, 2),
                    'status' => (string) $strategy->status,
                    'market_order_id' => $strategy->market_order_id,
                    'market_order_status' => $strategy->market_order_status,
                    'base_sell_gtt_trigger_id' => $strategy->base_sell_gtt_trigger_id,
                    'total_realized_pnl' => round((float) $strategy->total_realized_pnl, 2),
                    'total_unrealized_pnl' => round((float) $strategy->total_unrealized_pnl, 2),
                    'started_at' => $strategy->started_at ? (string) $strategy->started_at : null,
                ],
                'lot_ladder' => [
                    'summary' => $strategy->symbol.' | '.($summaryParts !== [] ? implode(' / ', $summaryParts) : 'No activity'),
                    'rows' => $rows,
                ],
                'positions' => $positionsPayload,
                'closed_trades' => $closedTradesPayload,
            ]);
        } catch (\Throwable $exception) {
            $this->logTradingError('Unable to fetch symbol data.', [
                'symbol' => $input['symbol'],
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load symbol data: '.$exception->getMessage(),
            ], 422);
        }
    }

    private function buildLevels(float $basePrice, float $buyOffset, float $sellOffset, int $lotsLimit): array
    {
        $levels = [];

        for ($index = 0; $index < $lotsLimit; $index += 1) {
            $buyPrice = round($basePrice - ($index * $buyOffset), 2);

            $levels[] = [
                'level' => $index + 1,
                'buy_price' => $buyPrice,
                'target_price' => round($buyPrice + $sellOffset, 2),
            ];
        }

        return $levels;
    }

    private function normalizeDateTimeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        if (is_array($value)) {
            $candidate = $value['date'] ?? $value['exchange_timestamp'] ?? $value['order_timestamp'] ?? null;

            if ($candidate instanceof \DateTimeInterface) {
                return $candidate;
            }

            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function toArray(mixed $value): array
    {
        return json_decode(json_encode($value), true) ?: [];
    }

    private function waitForExecutableMarketOrder(
        KiteConnect $kite,
        string $orderId,
        int $attempts = 6,
        int $sleepMilliseconds = 500
    ): array {
        if ($orderId === '') {
            return [
                'success' => false,
                'message' => 'Zerodha did not return a valid market order id.',
                'data' => null,
            ];
        }

        $latestStep = null;

        for ($attempt = 0; $attempt < $attempts; $attempt += 1) {
            $history = $this->toArray($kite->getOrderHistory($orderId));
            $latestStep = is_array($history) && $history !== [] ? end($history) : null;

            if (! is_array($latestStep)) {
                usleep($sleepMilliseconds * 1000);
                continue;
            }

            $status = strtoupper((string) ($latestStep['status'] ?? ''));

            if ($status === KiteConnect::STATUS_COMPLETE) {
                return [
                    'success' => true,
                    'message' => 'Base market order completed successfully.',
                    'data' => $latestStep,
                ];
            }

            if (in_array($status, [KiteConnect::STATUS_REJECTED, KiteConnect::STATUS_CANCELLED], true)) {
                $reason = $latestStep['status_message'] ?? $latestStep['status_message_raw'] ?? 'Order was not executed.';

                return [
                    'success' => false,
                    'message' => 'Base market order failed: '.$reason,
                    'data' => $latestStep,
                ];
            }

            usleep($sleepMilliseconds * 1000);
        }

        $finalStatus = strtoupper((string) ($latestStep['status'] ?? 'UNKNOWN'));
        $reason = is_array($latestStep)
            ? ($latestStep['status_message'] ?? $latestStep['status_message_raw'] ?? 'Order is still not complete.')
            : 'Order history could not be confirmed.';

        return [
            'success' => false,
            'message' => 'Base market order is not complete yet (status: '.$finalStatus.'). '.$reason,
            'data' => $latestStep,
        ];
    }

    private function logTradingError(string $message, array $context = []): void
    {
        TradingErrorLogger::write('error', $message, $context);
    }

    private function buildPositionsRows(string $symbol, array $instrument, array $positions): array
    {
        $netPositions = is_array($positions['net'] ?? null) ? $positions['net'] : [];
        $rows = [];

        foreach ($netPositions as $position) {
            if (
                strtoupper((string) ($position['exchange'] ?? '')) !== strtoupper((string) ($instrument['exchange'] ?? ''))
                || strtoupper((string) ($position['tradingsymbol'] ?? '')) !== strtoupper((string) ($instrument['tradingsymbol'] ?? ''))
                || (int) ($position['quantity'] ?? 0) === 0
            ) {
                continue;
            }

            $rows[] = [
                'symbol' => $symbol,
                'tradingsymbol' => $position['tradingsymbol'] ?? $symbol,
                'exchange' => $position['exchange'] ?? ($instrument['exchange'] ?? ''),
                'product' => $position['product'] ?? '',
                'quantity' => (int) ($position['quantity'] ?? 0),
                'average_price' => round((float) ($position['average_price'] ?? 0), 2),
                'last_price' => round((float) ($position['last_price'] ?? 0), 2),
                'buy_quantity' => (int) ($position['buy_quantity'] ?? 0),
                'sell_quantity' => (int) ($position['sell_quantity'] ?? 0),
                'pnl' => round((float) ($position['pnl'] ?? 0), 2),
                'unrealised' => round((float) ($position['unrealised'] ?? 0), 2),
                'realised' => round((float) ($position['realised'] ?? 0), 2),
            ];
        }

        return $rows;
    }

    private function buildClosedTradesRows(string $kiteUserId, string $symbol): array
    {
        $levels = $this->TradeStrategyLevel
            ->newQuery()
            ->with('strategy')
            ->where('kite_user_id', $kiteUserId)
            ->where(function ($query) {
                $query->whereNotNull('sell_executed_at')
                    ->orWhere('sell_order_status', KiteConnect::STATUS_COMPLETE)
                    ->orWhere('status', 'closed');
            })
            ->whereHas('strategy', function ($query) use ($symbol) {
                $query->where('symbol', $symbol);
            })
            ->orderByDesc('sell_executed_at')
            ->orderByDesc('updated_at')
            ->get();

        return $levels->map(function (TradeStrategyLevel $level) use ($symbol) {
            return [
                'trade_strategy_id' => $level->trade_strategy_id,
                'level' => (int) $level->level_no,
                'symbol' => $symbol,
                'buy_price' => round((float) ($level->buy_executed_price ?? $level->buy_price ?? 0), 2),
                'sell_price' => round((float) ($level->sell_executed_price ?? $level->target_price ?? 0), 2),
                'quantity' => (int) ($level->quantity ?? 0),
                'pnl' => round((float) ($level->realized_pnl ?? 0), 2),
                'closed_at' => $level->sell_executed_at
                    ? (string) $level->sell_executed_at
                    : ($level->updated_at ? (string) $level->updated_at : null),
                'status' => strtoupper((string) ($level->sell_order_status ?? 'COMPLETE')),
            ];
        })->values()->all();
    }

    private function findOpenOrderForLevel(array $orders, TradeStrategyLevel $level): ?array
    {
        foreach ($orders as $order) {
            $status = strtoupper((string) ($order['status'] ?? ''));

            if (! in_array($status, ['OPEN', 'OPEN PENDING', 'TRIGGER PENDING', 'VALIDATION PENDING', 'MODIFY PENDING'], true)) {
                continue;
            }

            if (
                (string) ($order['order_id'] ?? '') === (string) ($level->buy_order_id ?? '')
                || (string) ($order['order_id'] ?? '') === (string) ($level->sell_order_id ?? '')
            ) {
                return $order;
            }

            if ((int) ($order['quantity'] ?? 0) !== (int) $level->quantity) {
                continue;
            }

            $transactionType = strtoupper((string) ($order['transaction_type'] ?? ''));
            $price = (float) ($order['price'] ?? 0);

            if (
                $transactionType === 'BUY'
                && abs($price - (float) $level->buy_price) < 0.10
            ) {
                return $order;
            }

            if (
                $transactionType === 'SELL'
                && abs($price - (float) $level->target_price) < 0.10
            ) {
                return $order;
            }
        }

        return null;
    }

    private function resolveLadderStatus(
        TradeStrategyLevel $level,
        ?array $buyGtt,
        ?array $sellGtt,
        ?array $openOrder,
        ?array $position
    ): string {
        if ($level->sell_executed_at) {
            return 'CLOSED';
        }

        if ($openOrder) {
            return 'OPEN';
        }

        if ($position && (int) ($position['quantity'] ?? 0) !== 0 && $level->buy_executed_price) {
            return 'HELD';
        }

        if (strtolower((string) ($sellGtt['status'] ?? '')) === 'active') {
            return 'HELD';
        }

        if (strtolower((string) ($sellGtt['status'] ?? '')) === 'triggered') {
            return 'OPEN';
        }

        if (strtolower((string) ($buyGtt['status'] ?? '')) === 'active') {
            return 'PENDING';
        }

        if (strtolower((string) ($buyGtt['status'] ?? '')) === 'triggered') {
            return 'OPEN';
        }

        if ((string) $level->status === 'sell_gtt_pending') {
            return 'HELD';
        }

        return 'PENDING';
    }

}
