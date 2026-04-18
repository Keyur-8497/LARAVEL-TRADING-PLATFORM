<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeStrategyLevel extends Model
{
    use HasFactory;

    protected $table = 'trade_strategy_levels';

    protected $primaryKey = 'trade_strategy_levels_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'trade_strategy_levels_id',
        'trade_strategy_id',
        'kite_user_id',
        'level_no',
        'buy_price',
        'target_price',
        'quantity',
        'status',
        'buy_gtt_trigger_id',
        'buy_order_id',
        'buy_order_status',
        'buy_executed_price',
        'buy_executed_at',
        'sell_gtt_trigger_id',
        'sell_order_id',
        'sell_order_status',
        'sell_executed_price',
        'sell_executed_at',
        'realized_pnl',
        'failure_reason',
        'meta',
    ];

    public function InsertData(array $input): self
    {
        return static::create(Arr::only($input, $this->fillable));
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(TradeStrategy::class, 'trade_strategy_id', 'trade_strategy_id');
    }
}
