<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'visitor_name',
        'exhibit_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    protected $appends = [
        'visitorId',
        'visitorName',
        'exhibitId',
    ];

    public function getVisitorIdAttribute()
    {
        return $this->attributes['visitor_id'] ?? null;
    }

    public function getVisitorNameAttribute()
    {
        return $this->attributes['visitor_name'] ?? null;
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
