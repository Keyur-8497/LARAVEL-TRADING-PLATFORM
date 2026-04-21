<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_postback_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('trade_postback_log_id')->primary();
            $table->string('order_id')->nullable()->index();
            $table->string('kite_user_id')->nullable()->index();
            $table->string('symbol')->nullable()->index();
            $table->string('exchange', 20)->nullable();
            $table->string('transaction_type', 20)->nullable()->index();
            $table->string('status', 30)->nullable()->index();
            $table->boolean('checksum_verified')->default(false)->index();
            $table->boolean('processed_successfully')->default(false)->index();
            $table->text('processing_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_postback_logs');
    }
};
