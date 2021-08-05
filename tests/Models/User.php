<?php

namespace Tests\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use FriendsOfCat\Couchbase\Eloquent\Model as Eloquent;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Eloquent implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable;
    use CanResetPassword;

    protected $connection = 'couchbase';
    protected $dates = ['birthday', 'entry.date'];
    protected static $unguarded = true;

    public function books()
    {
        return $this->hasMany(Book::class, 'author_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function role()
    {
        return $this->hasOne(Role::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, null, 'users', 'groups');
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'imageable');
    }

    public function addresses()
    {
        return $this->embedsMany(Address::class);
    }

    public function father()
    {
        return $this->embedsOne(User::class);
    }

    public function getDateFormat()
    {
        return 'l jS \of F Y h:i:s A';
    }
}
