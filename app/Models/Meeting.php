<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $table = 'meetings';

    protected $fillable = [
        'admin_id',
        'title',
        'description',
        'status',
        'final_date',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'meeting_users');
    }

    public function pollOptions()
    {
        return $this->hasMany(PollOption::class);
    }

    public function notes()
    {
        return $this->hasOne(MeetingNote::class);
    }
}
