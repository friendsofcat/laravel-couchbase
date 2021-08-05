<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use FriendsOfCat\Couchbase\Console\Commands\BucketFlush;
use FriendsOfCat\Couchbase\Console\Commands\BucketCreate;
use FriendsOfCat\Couchbase\Console\Commands\BucketDelete;
use FriendsOfCat\Couchbase\Console\Commands\BucketCreatePrimaryIndex;

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
        // bucket flushing is too slow.
        // Workaround: recreating the bucket ?
        Artisan::call(BucketFlush::class);
//     Artisan::call(BucketDelete::class);
//     Artisan::call(BucketCreate::class);
//     try {
//       Artisan::call(BucketCreatePrimaryIndex::class);
//     } catch (\Couchbase\BaseException $e) {
//       // gracefully ignoring
//     }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }
}
