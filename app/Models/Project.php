<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
    'user_id', 'title', 'image_path', 'status', 
    'material_detected', 'ai_suggestions',
    'is_for_sale', 'price', 'description' // <--- Added these
];

// Add relationship to owner
public function user() {
    return $this->belongsTo(User::class);
}
}
