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
        Schema::create('items', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // OSRS Item ID
            $table->string('name')->index();
            $table->text('examine')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('members')->default(false);
            $table->integer('limit')->nullable();
            $table->integer('high_alch')->nullable();
            $table->integer('last_high_price')->nullable();
            $table->integer('last_low_price')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
