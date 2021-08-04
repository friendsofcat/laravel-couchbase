<?php

namespace Tests\Models;

use FriendsOfCat\Couchbase\Eloquent\Model as Eloquent;

class Address extends Eloquent {
    protected $connection = 'couchbase';
    protected static $unguarded = true;

    public function addresses() {
        return $this->embedsMany(Address::class);
    }
}
