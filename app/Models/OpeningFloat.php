<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningFloat extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'amount', 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
