<?php

namespace App\Models;

use Carbon\Carbon;
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
        'visitDate',
        'visitTime',
        'status',
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

    private function getCheckInTimeInMuseumTimezone(): ?Carbon
    {
        $value = $this->attributes['check_in_time'] ?? null;

        return $value
            ? Carbon::parse($value, 'UTC')->setTimezone('Asia/Manila')
            : null;
    }

    public function getVisitDateAttribute()
    {
        return $this->getCheckInTimeInMuseumTimezone()?->toDateString();
    }

    public function getVisitTimeAttribute()
    {
        return $this->getCheckInTimeInMuseumTimezone()?->format('H:i');
    }

    public function getStatusAttribute()
    {
        return $this->getIsCheckedInAttribute() ? 'Checked-in' : 'Checked-out';
    }

    public function getCheckInTimeAttribute()
    {
        return $this->getCheckInTimeInMuseumTimezone()?->toIso8601String();
    }

    public function getCheckOutTimeAttribute()
    {
        $val = $this->attributes['check_out_time'] ?? null;
        if (! $val) {
            return null;
        }

        return Carbon::parse($val, 'UTC')->setTimezone('Asia/Manila')->toIso8601String();
    }

    public function getIsCheckedInAttribute()
    {
        return (bool) ($this->attributes['is_checked_in'] ?? true);
    }

    public function artifactLogs()
    {
        return $this->hasMany(ArtifactLog::class, 'visitor_id', 'id');
    }
}
