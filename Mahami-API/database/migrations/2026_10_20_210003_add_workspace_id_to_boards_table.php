<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('user_id')
                ->constrained('workspaces')
                ->cascadeOnDelete();

            $table->index(['workspace_id', 'created_at']);
        });

        // Backward compatibility data migration:
        // Provision a default workspace for every user who already has boards
        $userIds = DB::table('boards')->whereNull('workspace_id')->distinct()->pluck('user_id');
        $now = now();

        foreach ($userIds as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            $workspaceName = $user ? ($user->name . ' Workspace') : 'Default Workspace';
            $slug = Str::slug($workspaceName) . '-' . $userId . '-' . Str::lower(Str::random(6));

            $workspaceId = DB::table('workspaces')->insertGetId([
                'owner_id' => $userId,
                'name' => $workspaceName,
                'slug' => $slug,
                'description' => 'Default workspace',
                'is_archived' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('workspace_members')->insert([
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'role' => 'owner',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('boards')
                ->where('user_id', $userId)
                ->whereNull('workspace_id')
                ->update(['workspace_id' => $workspaceId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropIndex(['workspace_id', 'created_at']);
            $table->dropColumn('workspace_id');
        });
    }
};

