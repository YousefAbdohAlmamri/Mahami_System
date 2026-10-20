<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BoardTeam extends Pivot
{
    protected $table = 'board_teams';

    public $incrementing = true;

    protected $fillable = [
        'board_id',
        'team_id',
        'permission',
    ];
}
