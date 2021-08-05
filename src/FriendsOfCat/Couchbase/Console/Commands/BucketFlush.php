<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Illuminate\Support\Facades\DB;
use FriendsOfCat\Couchbase\Connection;

/**
 * NOTES
 * -----
 * At this date (SDK version 3), flushing is slow.
 * Consider recreating the bucket (delete, create with index). See implementation on RefreshDatabase trait
 *
 * Class BucketFlush
 * @package FriendsOfCat\Couchbase\Console\Commands
 */
class BucketFlush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:bucket:flush {--bucket=} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all data on bucket (clear database).';

    public function handle()
    {
        $config = config('database.connections.couchbase');
        $bucket = $this->option('bucket') ?? $config['bucket'];
        /** @var Connection $couchbase */
        $couchbase = DB::connection('couchbase');
        $couchbase->flushBucket($bucket);
        $this->info(sprintf("Couchbase bucket '%s' flushed", $bucket));
    }
}
