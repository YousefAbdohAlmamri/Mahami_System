<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    protected $table = 'workspace_teams';

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'created_by',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class, 'board_teams', 'team_id', 'board_id')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function hasMember(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        return $this->members()->where('users.id', $userId)->exists();
    }

    public function getMemberRole(User|int $user): ?string
    {
        $userId = $user instanceof User ? $user->id : $user;
        $member = $this->members()->where('users.id', $userId)->first();
        return $member?->pivot?->role;
    }

    public function isLead(User|int $user): bool
    {
        return $this->getMemberRole($user) === 'lead';
    }
}
