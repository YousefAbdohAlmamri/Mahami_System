<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklistItem extends Model
{
    use HasFactory;

    protected $table = 'task_checklist_items';

    protected $fillable = [
        'checklist_id',
        'title',
        'is_completed',
        'position',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'position' => 'integer',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(TaskChecklist::class, 'checklist_id');
    }
}

