<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use FriendsOfCat\Couchbase\Console\Commands\BucketFlush;

trait RefreshDatabase {
  /**
   * Define hooks to migrate the database before and after each test.
   *
   * @return void
   */
  public function refreshDatabase()
  {
    Artisan::call(BucketFlush::class);
  }
  
  protected function setUp (): void {
    parent::setUp();
    $this->refreshDatabase();
  }
}
