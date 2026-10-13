<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'pass_code',
        'visitor_name',
        'address',
        'sex',
        'age',
        'contact_number',
        'email',
        'group_name',
        'group_type',
        'pax',
        'purpose',
        'tour_guide',
        'notes',
        'check_in_time',
        'check_out_time',
        'is_checked_in',
    ];

    protected $casts = [
        'age' => 'integer',
        'pax' => 'integer',
        'is_checked_in' => 'boolean',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    protected $appends = [
        'passCode',
        'visitorName',
        'contactNumber',
        'groupName',
        'groupType',
        'tourGuide',
        'checkInTime',
        'checkOutTime',
        'isCheckedIn',
    ];

    public function getPassCodeAttribute()
    {
        return $this->attributes['pass_code'] ?? null;
    }

    public function getVisitorNameAttribute()
    {
        return $this->attributes['visitor_name'] ?? null;
    }

    public function getContactNumberAttribute()
    {
        return $this->attributes['contact_number'] ?? null;
    }

    public function getGroupNameAttribute()
    {
        return $this->attributes['group_name'] ?? null;
    }

    public function getGroupTypeAttribute()
    {
        return $this->attributes['group_type'] ?? null;
    }

    public function getTourGuideAttribute()
    {
        return $this->attributes['tour_guide'] ?? null;
    }

    public function getCheckInTimeAttribute()
    {
        $val = $this->attributes['check_in_time'] ?? null;
        if (!$val) return null;
        return is_string($val) ? $val : \Carbon\Carbon::parse($val)->toISOString();
    }

    public function getCheckOutTimeAttribute()
    {
        $val = $this->attributes['check_out_time'] ?? null;
        if (!$val) return null;
        return is_string($val) ? $val : \Carbon\Carbon::parse($val)->toISOString();
    }

    public function getIsCheckedInAttribute()
    {
        return (bool)($this->attributes['is_checked_in'] ?? true);
    }

    public function artifactLogs()
    {
        return $this->hasMany(ArtifactLog::class, 'visitor_id', 'id');
    }
}
