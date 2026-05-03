<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->enum('type', ['IN', 'OUT']);
            $table->unsignedInteger('qty');
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->timestamps();

            $table->index('item_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
