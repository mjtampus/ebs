<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_drops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')->constrained('users');
            $table->foreignId('opening_float_id')->constrained('opening_floats')->onDelete('cascade');
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            $table->index(['cashier_id', 'date']);
            $table->index('opening_float_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_drops');
    }
};