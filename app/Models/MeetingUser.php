<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MeetingUser extends Pivot
{
    protected $table = 'meeting_users';

    protected $fillable = [
        'meeting_id',
        'user_id',
    ];
}
