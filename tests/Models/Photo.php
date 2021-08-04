<?php

namespace Tests\Models;
use FriendsOfCat\Couchbase\Eloquent\Model as Eloquent;

class Photo extends Eloquent {
    protected $connection = 'couchbase';
    protected $table = 'photos';
    protected static $unguarded = true;

    public function imageable() {
        return $this->morphTo();
    }
}
