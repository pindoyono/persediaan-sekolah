<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->enum('type', ['IN', 'OUT']);
            $table->date('tanggal');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('tanggal');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
