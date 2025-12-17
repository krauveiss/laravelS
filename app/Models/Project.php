<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'subject',
        'action_status',
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function members()
    {
        return $this->hasMany(ProjectMembership::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_memberships')
            ->withPivot(['role', 'is_owner'])
            ->withTimestamps();
    }
}
