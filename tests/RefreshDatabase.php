<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use FriendsOfCat\Couchbase\Console\Commands\BucketFlush;

/**
 * Since there is no migration we only need to make sure
 * - bucket and bucket index are created only before all tests (setUpBeforeClass method)
 * - bucket is flushed between two tests (setUp method)
 * Trait RefreshDatabase
 * @package Tests
 */
trait RefreshDatabase
{
    public function refreshDatabase()
    {
        // TODO: bucket flushing is too slow.
        // Workaround: recreating the bucket ?
        Artisan::call(BucketFlush::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }
}
