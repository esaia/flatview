<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = ['label', 'href', 'image', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];
}
