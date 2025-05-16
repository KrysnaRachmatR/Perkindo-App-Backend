<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollResponse extends Model
{
    protected $table = 'poll_responses';

    protected $fillable = [
        'poll_option_id',
        'user_id',
        'response',
    ];

    public function pollOption()
    {
        return $this->belongsTo(PollOption::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
