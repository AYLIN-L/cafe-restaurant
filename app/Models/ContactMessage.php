<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
    ];

    protected $table = 'contact_messages';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
