<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('item_alerts', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('type')->default('price')->after('item_id');
            $table->decimal('target_value', 16, 2)->nullable()->after('type');
            $table->integer('cooldown_minutes')->default(60)->after('webhook_url');
            $table->timestamp('last_notified_at')->nullable()->after('cooldown_minutes');
            $table->unsignedBigInteger('threshold_price')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_alerts', function (Blueprint $table) {
            //
        });
    }
};
