<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class TradePostbackLog extends Model
{
    protected $table = 'trade_postback_logs';

    protected $primaryKey = 'trade_postback_log_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'trade_postback_log_id',
        'order_id',
        'kite_user_id',
        'symbol',
        'exchange',
        'transaction_type',
        'status',
        'checksum_verified',
        'processed_successfully',
        'processing_message',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'checksum_verified' => 'boolean',
        'processed_successfully' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function InsertData(array $input): self
    {
        return static::create(Arr::only($input, $this->fillable));
    }
}
