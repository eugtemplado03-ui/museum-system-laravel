<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtifactLog extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'visitor_id',
        'exhibit_id',
        'action',
        'notes',
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

    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'id');
    }
}
