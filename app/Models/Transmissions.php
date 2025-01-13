<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transmissions extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'filename', 'status'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function getTransmissions($receiverId)
    {
        return $this->where('receiver_id', $receiverId)->get();
    }
}
