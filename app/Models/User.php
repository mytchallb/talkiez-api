<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_combined',
        'language',
        'friends',
        'friend_requests',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sentFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'sender_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'recipient_id');
    }

    public function friends()
    {
        return $this->hasManyThrough(
            User::class,
            Friendship::class,
            'sender_id', // Foreign key on friendships table
            'id', // Foreign key on users table
            'id', // Local key on users table
            'recipient_id' // Local key on friendships table
        )->where('status', 'accepted')
        ->union(
            $this->hasManyThrough(
                User::class,
                Friendship::class,
                'recipient_id',
                'id',
                'id',
                'sender_id'
            )->where('status', 'accepted')
        );
    }
}
