<?php

namespace Tests\Models;
use FriendsOfCat\Couchbase\Eloquent\Model as Eloquent;
use FriendsOfCat\Couchbase\Eloquent\SoftDeletes;

class Soft extends Eloquent {
    use SoftDeletes;

    protected $connection = 'couchbase';
    protected $table = 'soft';
    protected static $unguarded = true;
    protected $dates = ['deleted_at'];
}
