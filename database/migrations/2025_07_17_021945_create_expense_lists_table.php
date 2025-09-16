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
        Schema::create('expense_lists', function (Blueprint $table) {
            $table->id();
            $table->string('expense_name');
            // $table->boolean('is_raw')->default(false);
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('quantity')->nullable();
            $table->string('type');
            $table->float('unit_price')->nullable();
            $table->float('total_amount');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_lists');
    }
};
