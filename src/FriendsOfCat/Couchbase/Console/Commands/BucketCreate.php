<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Illuminate\Support\Facades\DB;
use FriendsOfCat\Couchbase\Connection;

class BucketCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:bucket:create
                            {--bucket=}
                            {--bucket-ram=1024}
                            {--enable-flush=false : Self explanatory. Flush disabled by default for dev environments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a bucket.';

    public function handle()
    {
        $config = config('database.connections.couchbase');
        $bucket = $this->option('bucket') ?? $config['bucket'];
        $bucketRam = $this->option('bucket-ram');

        /** @var Connection $couchbase */
        $couchbase = DB::connection('couchbase');
        $couchbase->createBucket(
            (new \Couchbase\BucketSettings())
          ->setName($bucket)
          ->setBucketType('couchbase')
          ->setRamQuotaMb($bucketRam)
          ->enableFlush($this->option('enable-flush'))
        );
        $this->info(sprintf("Couchbase bucket '%s' created with %s ram", $bucket, $bucketRam));
    }
}
