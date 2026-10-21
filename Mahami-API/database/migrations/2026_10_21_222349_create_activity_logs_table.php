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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();

            $table->unsignedBigInteger('board_id')->index();
            $table->foreign('board_id')->references('id')->on('boards')->cascadeOnDelete();

            $table->unsignedBigInteger('task_id')->nullable()->index();
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();

            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->string('action', 50)->index();
            $table->string('description', 255);
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
