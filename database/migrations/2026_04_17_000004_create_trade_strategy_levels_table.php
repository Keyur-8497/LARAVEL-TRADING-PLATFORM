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
        Schema::create('trade_strategy_levels', function (Blueprint $table) {
            $table->unsignedBigInteger('trade_strategy_levels_id')->primary();
            $table->unsignedBigInteger('trade_strategy_id');
            $table->string('kite_user_id')->index();
            $table->unsignedInteger('level_no');
            $table->decimal('buy_price', 15, 2);
            $table->decimal('target_price', 15, 2);
            $table->unsignedInteger('quantity');
            $table->string('status')->index();
            $table->unsignedBigInteger('buy_gtt_trigger_id')->nullable()->index();
            $table->string('buy_order_id')->nullable()->index();
            $table->string('buy_order_status')->nullable()->index();
            $table->decimal('buy_executed_price', 15, 2)->nullable();
            $table->timestamp('buy_executed_at')->nullable()->index();
            $table->unsignedBigInteger('sell_gtt_trigger_id')->nullable()->index();
            $table->string('sell_order_id')->nullable()->index();
            $table->string('sell_order_status')->nullable()->index();
            $table->decimal('sell_executed_price', 15, 2)->nullable();
            $table->timestamp('sell_executed_at')->nullable()->index();
            $table->decimal('realized_pnl', 15, 2)->default(0);
            $table->text('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('trade_strategy_id')
                ->references('trade_strategy_id')
                ->on('trade_strategies')
                ->cascadeOnDelete();

            $table->unique(['trade_strategy_id', 'level_no'], 'trade_strategy_levels_strategy_level_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_strategy_levels');
    }
};
