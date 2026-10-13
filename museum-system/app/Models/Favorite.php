<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'exhibit_id',
    ];

    protected $appends = [
        'visitorId',
        'exhibitId',
    ];

    public function getVisitorIdAttribute()
    {
        return $this->attributes['visitor_id'] ?? null;
    }

    public function getExhibitIdAttribute()
    {
        return $this->attributes['exhibit_id'] ?? null;
    }

    public function exhibit()
    {
        return $this->belongsTo(Exhibit::class, 'exhibit_id', 'id');
    }
}
