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
        Schema::create('board_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('board_id')->index();
            $table->string('name');
            $table->string('color', 32)->default('#3b82f6');
            $table->timestamps();

            $table->foreign('board_id')->references('id')->on('boards')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_labels');
    }
};

