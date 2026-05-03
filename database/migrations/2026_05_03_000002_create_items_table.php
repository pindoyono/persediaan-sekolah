<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('satuan', 30);
            $table->unsignedInteger('min_stock')->default(0);
            $table->timestamps();

            $table->index('category_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
