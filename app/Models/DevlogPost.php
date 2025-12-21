<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevlogPost extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'title', 'content'];
}
