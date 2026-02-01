<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MakerProfile extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    protected $fillable = [
        'user_id',
        'field',       // e.g., Woodworking, Metal
        'experience',  // Years of experience
        'portfolio',   // JSON or text
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}