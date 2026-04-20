<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradeStrategy extends Model
{
    use HasFactory;

    protected $table = 'trade_strategies';

    protected $primaryKey = 'trade_strategy_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'trade_strategy_id',
        'kite_user_id',
        'symbol',
        'exchange',
        'tradingsymbol',
        'base_price',
        'buy_offset',
        'sell_offset',
        'lot_size',
        'lots_limit',
        'capital_limit',
        'status',
        'market_order_id',
        'market_order_status',
        'base_sell_gtt_trigger_id',
        'total_realized_pnl',
        'total_unrealized_pnl',
        'started_at',
        'completed_at',
        'failure_reason',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'base_price' => 'decimal:2',
        'buy_offset' => 'decimal:2',
        'sell_offset' => 'decimal:2',
        'capital_limit' => 'decimal:2',
        'total_realized_pnl' => 'decimal:2',
        'total_unrealized_pnl' => 'decimal:2',
    ];

    public function InsertData(array $input): self
    {
        return static::create(Arr::only($input, $this->fillable));
    }

    public function levels(): HasMany
    {
        return $this->hasMany(TradeStrategyLevel::class, 'trade_strategy_id', 'trade_strategy_id');
    }
}
