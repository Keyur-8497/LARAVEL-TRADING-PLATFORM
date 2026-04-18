<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trade_strategies', function (Blueprint $table) {
            $table->unsignedBigInteger('trade_strategy_id')->primary();
            $table->string('kite_user_id')->index();
            $table->string('symbol')->index();
            $table->string('exchange', 20);
            $table->string('tradingsymbol')->index();
            $table->decimal('base_price', 15, 2);
            $table->decimal('buy_offset', 15, 2);
            $table->decimal('sell_offset', 15, 2);
            $table->unsignedInteger('lot_size');
            $table->unsignedInteger('lots_limit');
            $table->decimal('capital_limit', 15, 2);
            $table->string('status')->index();
            $table->string('market_order_id')->nullable()->index();
            $table->string('market_order_status')->nullable()->index();
            $table->unsignedBigInteger('base_sell_gtt_trigger_id')->nullable()->index();
            $table->decimal('total_realized_pnl', 15, 2)->default(0);
            $table->decimal('total_unrealized_pnl', 15, 2)->default(0);
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->text('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_strategies');
    }
};
