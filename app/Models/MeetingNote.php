<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNote extends Model
{
    protected $table = 'meeting_notes';

    protected $fillable = [
        'meeting_id',
        'notulen_id',
        'summary',
        'decisions',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function notulen()
    {
        return $this->belongsTo(Notulen::class);
    }
}
