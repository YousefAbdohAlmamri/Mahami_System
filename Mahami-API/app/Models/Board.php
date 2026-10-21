<?php

namespace App\Models;

use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'workspace_id',
        'title',
        'description',
    ];

    /**
     * Get the workspace that owns the board.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user that owns the board.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lists (columns) for the board.
     */
    public function lists(): HasMany
    {
        return $this->hasMany(BoardList::class)->orderBy('position');
    }

    /**
     * Labels defined on this board.
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /**
     * Activity logs recorded on this board.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    /**
     * Teams assigned to this board.
     */
    public function teams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'board_teams', 'board_id', 'team_id')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Resolve the effective permission of a user on this board.
     */
    public function getUserPermission(User $user): ?string
    {
        // 1. Direct board owner
        if ($this->user_id === $user->id) {
            return 'admin';
        }

        // 2. Workspace owner or admin
        if ($this->workspace_id && $this->workspace) {
            if ($this->workspace->owner_id === $user->id || $this->workspace->hasMemberRole($user, ['owner', 'admin'])) {
                return 'admin';
            }
        }

        // 3. Team-level permissions
        $teamIds = $user->teams()->pluck('workspace_teams.id');
        if ($teamIds->isNotEmpty()) {
            $assignedTeams = $this->teams()->whereIn('workspace_teams.id', $teamIds)->get();
            if ($assignedTeams->isNotEmpty()) {
                $permissions = $assignedTeams->pluck('pivot.permission')->all();
                if (in_array('admin', $permissions, true)) return 'admin';
                if (in_array('editor', $permissions, true)) return 'editor';
                if (in_array('viewer', $permissions, true)) return 'viewer';
            }
        }

        // 4. Workspace member access if board has no specific team restriction
        if ($this->workspace_id && $this->workspace && $this->workspace->hasMemberRole($user, 'member')) {
            if ($this->teams()->exists()) {
                return null;
            }
            return 'editor';
        }

        return null;
    }
}

