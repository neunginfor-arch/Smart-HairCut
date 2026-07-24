<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopClosure extends Model
{
    protected $fillable = ['start_date', 'end_date', 'note'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
