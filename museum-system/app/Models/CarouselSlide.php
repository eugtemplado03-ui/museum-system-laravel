<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarouselSlide extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'subtitle',
        'image_path',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'imagePath',
        'isActive',
    ];

    public function getImagePathAttribute()
    {
        return $this->attributes['image_path'] ?? null;
    }

    public function getIsActiveAttribute()
    {
        return (bool)($this->attributes['is_active'] ?? true);
    }
}
