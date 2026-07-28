<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'company', 'message', 'is_read'];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
