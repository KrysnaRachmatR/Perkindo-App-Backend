<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollOption extends Model
{
    protected $table = 'poll_options';

    protected $fillable = [
        'meeting_id',
        'option_date',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function responses()
    {
        return $this->hasMany(PollResponse::class);
    }
}
