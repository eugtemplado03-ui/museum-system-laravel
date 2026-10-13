<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'category',
        'image_path',
        'description',
    ];

    protected $appends = [
        'imagePath',
    ];

    public function getImagePathAttribute()
    {
        return $this->attributes['image_path'] ?? null;
    }
}
