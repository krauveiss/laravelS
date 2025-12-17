<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function projectMemberships()
    {
        return $this->hasMany(ProjectMembership::class, 'user_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_memberships', 'user_id', 'project_id')
            ->withPivot(['role', 'is_owner'])
            ->withTimestamps();
    }

    public function ownedTasks()
    {
        return $this->hasMany(Task::class, 'owner_id');
    }
}
