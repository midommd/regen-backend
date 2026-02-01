<?php

namespace App\Models;

// 1. IMPORT THIS
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // 2. ADD IT HERE inside the class
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',   // We added this
        'avatar',
        'bio', // We added this
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function makerProfile()
    {
        return $this->hasOne(MakerProfile::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}