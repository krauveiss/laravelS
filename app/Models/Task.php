<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id',
        'parent_task_id',
        'owner_id',
        'title',
        'description',
        'status',
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function updateStatus()
    {
        if ($this->subtasks->isNotEmpty() && $this->subtasks->every(fn($task) => $task->status == 2)) {
            $this->status = 2;
            $this->save();
        }
    }
}
