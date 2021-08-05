<?php

namespace Tests\Models;

use FriendsOfCat\Couchbase\Eloquent\Model as Eloquent;

class Group extends Eloquent
{
    protected $connection = 'couchbase';
    protected $table = 'groups';
    protected static $unguarded = true;

    public function users()
    {
        return $this->belongsToMany('User', null, 'groups', 'users');
    }
}
