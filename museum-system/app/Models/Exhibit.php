<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exhibit extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'code',
        'title',
        'category',
        'origin',
        'year',
        'location',
        'image_path',
        'description',
        'additional_details',
        'description_tl',
        'description_cb',
        'image_paths',
        'optimized_image_paths',
        'audio_path',
        'video_path',
        'floor',
        'map_zone',
        'pin_x',
        'pin_y',
        'directions',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'optimized_image_paths' => 'array',
        'floor' => 'integer',
        'pin_x' => 'float',
        'pin_y' => 'float',
    ];

    protected $appends = [
        'imagePath',
        'imagePaths',
        'additionalDetails',
        'audioPath',
        'videoPath',
        'mapZone',
        'pinX',
        'pinY',
    ];

    public function getImagePathAttribute()
    {
        return $this->attributes['image_path'] ?? null;
    }

    public function getImagePathsAttribute()
    {
        $val = $this->attributes['image_paths'] ?? null;
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            return is_array($decoded) ? $decoded : ($val ? [$val] : []);
        }
        return is_array($val) ? $val : ($this->image_path ? [$this->image_path] : []);
    }

    public function getAdditionalDetailsAttribute()
    {
        return $this->attributes['additional_details'] ?? null;
    }

    public function getAudioPathAttribute()
    {
        return $this->attributes['audio_path'] ?? null;
    }

    public function getVideoPathAttribute()
    {
        return $this->attributes['video_path'] ?? null;
    }

    public function getMapZoneAttribute()
    {
        return $this->attributes['map_zone'] ?? null;
    }

    public function getPinXAttribute()
    {
        return isset($this->attributes['pin_x']) ? (float)$this->attributes['pin_x'] : null;
    }

    public function getPinYAttribute()
    {
        return isset($this->attributes['pin_y']) ? (float)$this->attributes['pin_y'] : null;
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'exhibit_id', 'id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'exhibit_id', 'id');
    }
}
