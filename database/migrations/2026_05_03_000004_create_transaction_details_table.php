<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
