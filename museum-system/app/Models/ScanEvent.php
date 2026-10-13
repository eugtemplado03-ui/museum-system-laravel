<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'exhibit_id',
        'visitor_id',
        'source',
        'user_agent',
        'ip',
    ];

    protected $appends = [
        'exhibitId',
        'visitorId',
        'userAgent',
        'timestamp',
    ];

    public function getExhibitIdAttribute()
    {
        return $this->attributes['exhibit_id'] ?? null;
    }

    public function getVisitorIdAttribute()
    {
        return $this->attributes['visitor_id'] ?? null;
    }

    public function getUserAgentAttribute()
    {
        return $this->attributes['user_agent'] ?? null;
    }

    public function getTimestampAttribute()
    {
        return $this->created_at ? $this->created_at->toISOString() : null;
    }

    public function exhibit()
    {
        return $this->belongsTo(Exhibit::class, 'exhibit_id', 'id');
    }
}
