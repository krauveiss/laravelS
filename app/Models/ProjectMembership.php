<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMembership extends Model
{

    protected $fillable = [
        'user_id',
        'project_id',
        'role',
        'is_owner',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
