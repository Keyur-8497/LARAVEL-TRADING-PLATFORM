<?php

namespace App\Services;

use App\Models\TradePostbackLog;
use App\Models\TradeStrategy;
use App\Models\TradeStrategyLevel;
use App\Support\TradingErrorLogger;
use Illuminate\Support\Facades\DB;
use KiteConnect\KiteConnect;

class TradeStrategyPostbackService
{
    public function __construct(
        private readonly KiteSessionManager $kiteSessionManager,
        private readonly TradeStrategy $tradeStrategy,
        private readonly TradeStrategyLevel $tradeStrategyLevel,
        private readonly TradePostbackLog $tradePostbackLog,
    ) {
    }

    public function process(array $payload): array
    {
        $checksumVerified = $this->isValidChecksum($payload);
        $logRecord = $this->createPostbackLog($payload, $checksumVerified);

        if (! $checksumVerified) {
            $this->log('Invalid Zerodha postback checksum.', ['payload' => $payload]);
            $this->finalizePostbackLog($logRecord, false, 'Invalid checksum.');

            return [
                'success' => false,
                'message' => 'Invalid checksum.',
            ];
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $status = strtoupper((string) ($payload['status'] ?? ''));

        if ($orderId === '') {
            $this->finalizePostbackLog($logRecord, false, 'Missing order_id in postback payload.');

            return [
                'success' => false,
                'message' => 'Missing order_id in postback payload.',
            ];
        }

        $level = $this->findMatchingLevel($payload);
        if (! $level) {
            $this->log('No matching trade strategy level found for postback.', [
                'order_id' => $orderId,
                'payload' => $payload,
            ]);
            $this->finalizePostbackLog($logRecord, true, 'No matching strategy level found. Payload logged.');

            return [
                'success' => true,
                'message' => 'No matching strategy level found. Payload logged.',
            ];
        }

        if ($status === KiteConnect::STATUS_COMPLETE) {
            $result = DB::transaction(function () use ($level, $payload) {
                $transactionType = strtoupper((string) ($payload['transaction_type'] ?? ''));

                if ($transactionType === KiteConnect::TRANSACTION_TYPE_BUY) {
                    return $this->handleCompletedBuy($level, $payload);
                }

                if ($transactionType === KiteConnect::TRANSACTION_TYPE_SELL) {
                    return $this->handleCompletedSell($level, $payload);
                }

                return [
                    'success' => true,
                    'message' => 'Postback received but no action required for transaction type.',
                ];
            });

            $this->finalizePostbackLog($logRecord, (bool) ($result['success'] ?? false), (string) ($result['message'] ?? 'Processed.'));

            return $result;
        }

        if (in_array($status, [KiteConnect::STATUS_REJECTED, KiteConnect::STATUS_CANCELLED], true)) {
            $result = DB::transaction(function () use ($level, $payload, $status) {
                $transactionType = strtoupper((string) ($payload['transaction_type'] ?? ''));
                $failureReason = (string) ($payload['status_message'] ?? $payload['status_message_raw'] ?? 'Order update received from Zerodha.');

                if ($transactionType === KiteConnect::TRANSACTION_TYPE_BUY) {
                    $level->buy_order_id = (string) ($payload['order_id'] ?? $level->buy_order_id);
                    $level->buy_order_status = $status;
                } else {
                    $level->sell_order_id = (string) ($payload['order_id'] ?? $level->sell_order_id);
                    $level->sell_order_status = $status;
                }

                $level->failure_reason = $failureReason;
                $level->status = 'failed';
                $level->save();

                return [
                    'success' => true,
                    'message' => 'Rejected/cancelled order synced successfully.',
                ];
            });

            $this->finalizePostbackLog($logRecord, (bool) ($result['success'] ?? false), (string) ($result['message'] ?? 'Processed.'));

            return $result;
        }

        $this->finalizePostbackLog($logRecord, true, 'Postback received. No DB action required for status '.$status.'.');

        return [
            'success' => true,
            'message' => 'Postback received. No DB action required for status '.$status.'.',
        ];
    }

    private function handleCompletedBuy(TradeStrategyLevel $level, array $payload): array
    {
        if ($level->buy_order_status === KiteConnect::STATUS_COMPLETE && $level->sell_gtt_trigger_id) {
            return [
                'success' => true,
                'message' => 'Buy completion already processed.',
            ];
        }

        $level->buy_order_id = (string) ($payload['order_id'] ?? $level->buy_order_id);
        $level->buy_order_status = KiteConnect::STATUS_COMPLETE;
        $level->buy_executed_price = (float) ($payload['average_price'] ?? $payload['price'] ?? $level->buy_price);
        $level->buy_executed_at = $payload['exchange_timestamp'] ?? $payload['order_timestamp'] ?? now();

        if (! $level->sell_gtt_trigger_id) {
            $kite = $this->kiteSessionManager->makeClient();
            $strategy = $level->strategy;
            $sellGtt = $this->toArray($kite->placeGTT([
                'trigger_type' => KiteConnect::GTT_TYPE_SINGLE,
                'tradingsymbol' => (string) $strategy->tradingsymbol,
                'exchange' => (string) $strategy->exchange,
                'trigger_values' => [(float) $level->target_price],
                'last_price' => (float) ($level->buy_executed_price ?? $level->buy_price),
                'orders' => [[
                    'exchange' => (string) $strategy->exchange,
                    'tradingsymbol' => (string) $strategy->tradingsymbol,
                    'transaction_type' => KiteConnect::TRANSACTION_TYPE_SELL,
                    'quantity' => (int) $level->quantity,
                    'order_type' => KiteConnect::ORDER_TYPE_LIMIT,
                    'product' => KiteConnect::PRODUCT_CNC,
                    'price' => (float) $level->target_price,
                ]],
            ]));

            $level->sell_gtt_trigger_id = $sellGtt['trigger_id'] ?? null;
        }

        $level->status = 'sell_gtt_pending';
        $level->save();

        return [
            'success' => true,
            'message' => 'Completed buy synced and sell GTT created successfully.',
        ];
    }

    private function handleCompletedSell(TradeStrategyLevel $level, array $payload): array
    {
        if ($level->sell_order_status === KiteConnect::STATUS_COMPLETE && $level->status === 'closed') {
            return [
                'success' => true,
                'message' => 'Sell completion already processed.',
            ];
        }

        $buyPrice = (float) ($level->buy_executed_price ?? $level->buy_price ?? 0);
        $sellPrice = (float) ($payload['average_price'] ?? $payload['price'] ?? $level->target_price);
        $realizedPnl = ($sellPrice - $buyPrice) * (int) $level->quantity;

        $level->sell_order_id = (string) ($payload['order_id'] ?? $level->sell_order_id);
        $level->sell_order_status = KiteConnect::STATUS_COMPLETE;
        $level->sell_executed_price = $sellPrice;
        $level->sell_executed_at = $payload['exchange_timestamp'] ?? $payload['order_timestamp'] ?? now();
        $level->realized_pnl = round($realizedPnl, 2);
        $level->status = 'closed';
        $level->save();

        $strategy = $level->strategy;
        if ($strategy) {
            $strategy->total_realized_pnl = round((float) $strategy->levels()->sum('realized_pnl'), 2);
            $strategy->save();

            if ($this->isBaseLevel($level, $strategy)) {
                $this->handleBaseLevelRecycle($strategy, $level);
            } else {
                $this->handleLowerLevelRecycle($strategy, $level);
            }
        }

        return [
            'success' => true,
            'message' => 'Completed sell synced successfully.',
        ];
    }

    private function handleLowerLevelRecycle(TradeStrategy $strategy, TradeStrategyLevel $closedLevel): void
    {
        $kite = $this->kiteSessionManager->makeClient();
        $response = $this->toArray($kite->placeGTT([
            'trigger_type' => KiteConnect::GTT_TYPE_SINGLE,
            'tradingsymbol' => (string) $strategy->tradingsymbol,
            'exchange' => (string) $strategy->exchange,
            'trigger_values' => [(float) $closedLevel->buy_price],
            'last_price' => (float) $closedLevel->sell_executed_price,
            'orders' => [[
                'exchange' => (string) $strategy->exchange,
                'tradingsymbol' => (string) $strategy->tradingsymbol,
                'transaction_type' => KiteConnect::TRANSACTION_TYPE_BUY,
                'quantity' => (int) $closedLevel->quantity,
                'order_type' => KiteConnect::ORDER_TYPE_LIMIT,
                'product' => KiteConnect::PRODUCT_CNC,
                'price' => (float) $closedLevel->buy_price,
            ]],
        ]));

        $row = [];
        $row['trade_strategy_levels_id'] = (int) RendomString(10, 'number');
        $row['trade_strategy_id'] = (int) $strategy->trade_strategy_id;
        $row['kite_user_id'] = (string) $strategy->kite_user_id;
        $row['level_no'] = (int) $closedLevel->level_no;
        $row['buy_price'] = (float) $closedLevel->buy_price;
        $row['target_price'] = (float) $closedLevel->target_price;
        $row['quantity'] = (int) $closedLevel->quantity;
        $row['status'] = 'buy_gtt_pending';
        $row['buy_gtt_trigger_id'] = $response['trigger_id'] ?? null;
        $row['buy_order_id'] = null;
        $row['buy_order_status'] = null;
        $row['buy_executed_price'] = null;
        $row['buy_executed_at'] = null;
        $row['sell_gtt_trigger_id'] = null;
        $row['sell_order_id'] = null;
        $row['sell_order_status'] = null;
        $row['sell_executed_price'] = null;
        $row['sell_executed_at'] = null;
        $row['realized_pnl'] = 0;
        $row['failure_reason'] = null;
        $row['meta'] = [
            'is_base_level' => false,
            'recycled_from_level_id' => $closedLevel->trade_strategy_levels_id,
            'recycle_type' => 'lower_level',
        ];

        $this->tradeStrategyLevel->InsertData($row);
    }

    private function handleBaseLevelRecycle(TradeStrategy $strategy, TradeStrategyLevel $closedLevel): void
    {
        $this->cancelPendingBuyGtts($strategy);

        $newBasePrice = (float) $closedLevel->target_price;
        $kite = $this->kiteSessionManager->makeClient();
        $marketOrder = $this->toArray($kite->placeOrder(KiteConnect::VARIETY_REGULAR, [
            'tradingsymbol' => (string) $strategy->tradingsymbol,
            'exchange' => (string) $strategy->exchange,
            'quantity' => (int) $strategy->lot_size,
            'transaction_type' => KiteConnect::TRANSACTION_TYPE_BUY,
            'order_type' => KiteConnect::ORDER_TYPE_MARKET,
            'product' => KiteConnect::PRODUCT_CNC,
            'validity' => KiteConnect::VALIDITY_DAY,
            'market_protection' => KiteConnect::MARKET_PROTECTION_AUTO,
        ]));

        $verifiedMarketOrder = $this->waitForExecutableMarketOrder($kite, (string) ($marketOrder['order_id'] ?? ''));
        if (! ($verifiedMarketOrder['success'] ?? false)) {
            throw new \RuntimeException((string) ($verifiedMarketOrder['message'] ?? 'Unable to verify recycled base market order.'));
        }

        $baseOrderData = $verifiedMarketOrder['data'] ?? [];
        $sellTargetPrice = round($newBasePrice + (float) $strategy->sell_offset, 2);
        $sellTargetGtt = $this->toArray($kite->placeGTT([
            'trigger_type' => KiteConnect::GTT_TYPE_SINGLE,
            'tradingsymbol' => (string) $strategy->tradingsymbol,
            'exchange' => (string) $strategy->exchange,
            'trigger_values' => [$sellTargetPrice],
            'last_price' => $newBasePrice,
            'orders' => [[
                'exchange' => (string) $strategy->exchange,
                'tradingsymbol' => (string) $strategy->tradingsymbol,
                'transaction_type' => KiteConnect::TRANSACTION_TYPE_SELL,
                'quantity' => (int) $strategy->lot_size,
                'order_type' => KiteConnect::ORDER_TYPE_LIMIT,
                'product' => KiteConnect::PRODUCT_CNC,
                'price' => $sellTargetPrice,
            ]],
        ]));

        $this->insertRecycledLevelRow($strategy, 1, $newBasePrice, $sellTargetPrice, [
            'status' => 'sell_gtt_pending',
            'buy_order_id' => (string) ($baseOrderData['order_id'] ?? ''),
            'buy_order_status' => (string) ($baseOrderData['status'] ?? ''),
            'buy_executed_price' => (float) ($baseOrderData['average_price'] ?? $newBasePrice),
            'buy_executed_at' => $payloadTime = ($baseOrderData['exchange_timestamp'] ?? $baseOrderData['order_timestamp'] ?? now()),
            'sell_gtt_trigger_id' => $sellTargetGtt['trigger_id'] ?? null,
            'meta' => [
                'is_base_level' => true,
                'recycled_from_level_id' => $closedLevel->trade_strategy_levels_id,
                'recycle_type' => 'base_level',
            ],
        ]);

        for ($levelNo = 2; $levelNo <= (int) $strategy->lots_limit; $levelNo += 1) {
            $buyPrice = round($newBasePrice - (($levelNo - 1) * (float) $strategy->buy_offset), 2);
            $targetPrice = round($buyPrice + (float) $strategy->sell_offset, 2);
            $buyGtt = $this->toArray($kite->placeGTT([
                'trigger_type' => KiteConnect::GTT_TYPE_SINGLE,
                'tradingsymbol' => (string) $strategy->tradingsymbol,
                'exchange' => (string) $strategy->exchange,
                'trigger_values' => [$buyPrice],
                'last_price' => $newBasePrice,
                'orders' => [[
                    'exchange' => (string) $strategy->exchange,
                    'tradingsymbol' => (string) $strategy->tradingsymbol,
                    'transaction_type' => KiteConnect::TRANSACTION_TYPE_BUY,
                    'quantity' => (int) $strategy->lot_size,
                    'order_type' => KiteConnect::ORDER_TYPE_LIMIT,
                    'product' => KiteConnect::PRODUCT_CNC,
                    'price' => $buyPrice,
                ]],
            ]));

            $this->insertRecycledLevelRow($strategy, $levelNo, $buyPrice, $targetPrice, [
                'status' => 'buy_gtt_pending',
                'buy_gtt_trigger_id' => $buyGtt['trigger_id'] ?? null,
                'meta' => [
                    'is_base_level' => false,
                    'recycled_from_level_id' => $closedLevel->trade_strategy_levels_id,
                    'recycle_type' => 'base_level_rebuild',
                ],
            ]);
        }

        $strategy->base_price = $newBasePrice;
        $strategy->market_order_id = $baseOrderData['order_id'] ?? $strategy->market_order_id;
        $strategy->market_order_status = $baseOrderData['status'] ?? $strategy->market_order_status;
        $strategy->base_sell_gtt_trigger_id = $sellTargetGtt['trigger_id'] ?? $strategy->base_sell_gtt_trigger_id;
        $strategy->save();
    }

    private function cancelPendingBuyGtts(TradeStrategy $strategy): void
    {
        $pendingLevels = $strategy->levels()
            ->whereIn('status', ['buy_gtt_pending', 'pending', 'OPEN'])
            ->whereNull('buy_order_id')
            ->whereNotNull('buy_gtt_trigger_id')
            ->get();

        if ($pendingLevels->isEmpty()) {
            return;
        }

        $kite = $this->kiteSessionManager->makeClient();

        foreach ($pendingLevels as $pendingLevel) {
            try {
                $kite->deleteGTT((string) $pendingLevel->buy_gtt_trigger_id);
            } catch (\Throwable $exception) {
                $this->log('Unable to delete pending buy GTT during base recycle.', [
                    'trade_strategy_levels_id' => $pendingLevel->trade_strategy_levels_id,
                    'buy_gtt_trigger_id' => $pendingLevel->buy_gtt_trigger_id,
                    'message' => $exception->getMessage(),
                ]);
            }

            $pendingLevel->status = 'superseded';
            $pendingLevel->failure_reason = 'Superseded during base level recycle.';
            $pendingLevel->save();
        }
    }

    private function insertRecycledLevelRow(TradeStrategy $strategy, int $levelNo, float $buyPrice, float $targetPrice, array $overrides = []): TradeStrategyLevel
    {
        $row = [];
        $row['trade_strategy_levels_id'] = (int) RendomString(10, 'number');
        $row['trade_strategy_id'] = (int) $strategy->trade_strategy_id;
        $row['kite_user_id'] = (string) $strategy->kite_user_id;
        $row['level_no'] = $levelNo;
        $row['buy_price'] = $buyPrice;
        $row['target_price'] = $targetPrice;
        $row['quantity'] = (int) $strategy->lot_size;
        $row['status'] = $overrides['status'] ?? 'buy_gtt_pending';
        $row['buy_gtt_trigger_id'] = $overrides['buy_gtt_trigger_id'] ?? null;
        $row['buy_order_id'] = $overrides['buy_order_id'] ?? null;
        $row['buy_order_status'] = $overrides['buy_order_status'] ?? null;
        $row['buy_executed_price'] = $overrides['buy_executed_price'] ?? null;
        $row['buy_executed_at'] = $overrides['buy_executed_at'] ?? null;
        $row['sell_gtt_trigger_id'] = $overrides['sell_gtt_trigger_id'] ?? null;
        $row['sell_order_id'] = null;
        $row['sell_order_status'] = null;
        $row['sell_executed_price'] = null;
        $row['sell_executed_at'] = null;
        $row['realized_pnl'] = 0;
        $row['failure_reason'] = null;
        $row['meta'] = $overrides['meta'] ?? [];

        return $this->tradeStrategyLevel->InsertData($row);
    }

    private function isBaseLevel(TradeStrategyLevel $level, TradeStrategy $strategy): bool
    {
        $meta = is_array($level->meta) ? $level->meta : [];
        if (array_key_exists('is_base_level', $meta)) {
            return (bool) $meta['is_base_level'];
        }

        $maxBuyPrice = (float) $strategy->levels()
            ->whereNotIn('status', ['closed', 'failed', 'superseded'])
            ->max('buy_price');

        return (float) $level->buy_price >= $maxBuyPrice;
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
                    'message' => 'Market order completed successfully.',
                    'data' => $latestStep,
                ];
            }

            if (in_array($status, [KiteConnect::STATUS_REJECTED, KiteConnect::STATUS_CANCELLED], true)) {
                $reason = $latestStep['status_message'] ?? $latestStep['status_message_raw'] ?? 'Order was not executed.';

                return [
                    'success' => false,
                    'message' => 'Market order failed: '.$reason,
                    'data' => $latestStep,
                ];
            }

            usleep($sleepMilliseconds * 1000);
        }

        return [
            'success' => false,
            'message' => 'Market order could not be verified as complete in time.',
            'data' => $latestStep,
        ];
    }

    private function createPostbackLog(array $payload, bool $checksumVerified): TradePostbackLog
    {
        $row = [];
        $row['trade_postback_log_id'] = (int) RendomString(10, 'number');
        $row['order_id'] = (string) ($payload['order_id'] ?? '');
        $row['kite_user_id'] = (string) ($payload['user_id'] ?? '');
        $row['symbol'] = (string) ($payload['tradingsymbol'] ?? '');
        $row['exchange'] = (string) ($payload['exchange'] ?? '');
        $row['transaction_type'] = (string) ($payload['transaction_type'] ?? '');
        $row['status'] = (string) ($payload['status'] ?? '');
        $row['checksum_verified'] = $checksumVerified;
        $row['processed_successfully'] = false;
        $row['processing_message'] = null;
        $row['payload'] = $payload;
        $row['processed_at'] = null;

        return $this->tradePostbackLog->InsertData($row);
    }

    private function finalizePostbackLog(TradePostbackLog $logRecord, bool $success, string $message): void
    {
        $logRecord->processed_successfully = $success;
        $logRecord->processing_message = $message;
        $logRecord->processed_at = now();
        $logRecord->save();
    }

    private function findMatchingLevel(array $payload): ?TradeStrategyLevel
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $transactionType = strtoupper((string) ($payload['transaction_type'] ?? ''));
        $userId = (string) ($payload['user_id'] ?? '');
        $tradingsymbol = strtoupper((string) ($payload['tradingsymbol'] ?? ''));
        $exchange = strtoupper((string) ($payload['exchange'] ?? ''));
        $quantity = (int) ($payload['quantity'] ?? 0);
        $comparisonPrice = (float) ($payload['price'] ?? $payload['average_price'] ?? 0);

        $direct = $this->tradeStrategyLevel->newQuery()
            ->where(function ($query) use ($orderId) {
                $query->where('buy_order_id', $orderId)
                    ->orWhere('sell_order_id', $orderId);
            })
            ->first();

        if ($direct) {
            return $direct;
        }

        $query = $this->tradeStrategyLevel->newQuery()
            ->with('strategy')
            ->where('kite_user_id', $userId)
            ->where('quantity', $quantity)
            ->whereHas('strategy', function ($builder) use ($tradingsymbol, $exchange) {
                $builder->whereRaw('UPPER(tradingsymbol) = ?', [$tradingsymbol])
                    ->whereRaw('UPPER(exchange) = ?', [$exchange]);
            });

        if ($transactionType === KiteConnect::TRANSACTION_TYPE_BUY) {
            return $query
                ->whereNull('buy_order_id')
                ->where(function ($builder) use ($comparisonPrice) {
                    $builder->where('buy_price', $comparisonPrice)
                        ->orWhereBetween('buy_price', [$comparisonPrice - 0.10, $comparisonPrice + 0.10]);
                })
                ->orderBy('level_no')
                ->first();
        }

        return $query
            ->whereNotNull('sell_gtt_trigger_id')
            ->whereNull('sell_order_id')
            ->where(function ($builder) use ($comparisonPrice) {
                $builder->where('target_price', $comparisonPrice)
                    ->orWhereBetween('target_price', [$comparisonPrice - 0.10, $comparisonPrice + 0.10]);
            })
            ->orderBy('level_no')
            ->first();
    }

    private function isValidChecksum(array $payload): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $timestamp = (string) ($payload['order_timestamp'] ?? '');
        $checksum = (string) ($payload['checksum'] ?? '');
        $secret = (string) config('kite.api_secret');

        if ($orderId === '' || $timestamp === '' || $checksum === '' || $secret === '') {
            return false;
        }

        $expected = hash('sha256', $orderId.$timestamp.$secret);

        return hash_equals($expected, $checksum);
    }

    private function toArray(mixed $value): array
    {
        return json_decode(json_encode($value), true) ?: [];
    }

    private function log(string $message, array $context = []): void
    {
        TradingErrorLogger::write('info', $message, $context);
    }
}
