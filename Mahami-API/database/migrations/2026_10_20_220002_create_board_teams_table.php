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
        Schema::create('board_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('board_id')->index();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('permission', 32)->default('editor'); // viewer, editor, admin
            $table->timestamps();

            $table->unique(['board_id', 'team_id']);
            $table->foreign('board_id')->references('id')->on('boards')->cascadeOnDelete();
            $table->foreign('team_id')->references('id')->on('workspace_teams')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_teams');
    }
};
