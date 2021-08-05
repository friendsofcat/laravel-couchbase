<?php

namespace Tests\Models;

use FriendsOfCat\Couchbase\Eloquent\Model as Eloquent;

class Item extends Eloquent
{
    protected $connection = 'couchbase';
    protected $table = 'items';
    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function scopeSharp($query)
    {
        return $query->where('type', 'sharp');
    }
}
