<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_strategy_levels', function (Blueprint $table) {
            $table->dropForeign(['trade_strategy_id']);
            $table->dropUnique('trade_strategy_levels_strategy_level_unique');
            $table->index('trade_strategy_id', 'trade_strategy_levels_trade_strategy_id_index');
            $table->foreign('trade_strategy_id')
                ->references('trade_strategy_id')
                ->on('trade_strategies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trade_strategy_levels', function (Blueprint $table) {
            $table->dropForeign(['trade_strategy_id']);
            $table->dropIndex('trade_strategy_levels_trade_strategy_id_index');
            $table->unique(['trade_strategy_id', 'level_no'], 'trade_strategy_levels_strategy_level_unique');
            $table->foreign('trade_strategy_id')
                ->references('trade_strategy_id')
                ->on('trade_strategies')
                ->cascadeOnDelete();
        });
    }
};
