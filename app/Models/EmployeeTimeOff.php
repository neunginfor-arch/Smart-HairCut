<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTimeOff extends Model
{
    protected $fillable = ['employee_id', 'start_at', 'end_at', 'note'];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
