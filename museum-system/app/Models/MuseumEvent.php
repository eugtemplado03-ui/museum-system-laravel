<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MuseumEvent extends Model
{
    use HasFactory;

    protected $table = 'museum_events';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'description',
        'date',
        'location',
        'image_path',
    ];

    protected $appends = [
        'imagePath',
    ];

    public function getImagePathAttribute()
    {
        return $this->attributes['image_path'] ?? null;
    }
}
