<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Illuminate\Support\Facades\DB;
use FriendsOfCat\Couchbase\Connection;

class BucketDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:bucket:delete {--bucket=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a bucket.';

    public function handle()
    {
        /** @var Connection $couchbase */
        $couchbase = DB::connection('couchbase');
        $config = config('database.connections.couchbase');
        $bucket = $this->option('bucket') ?? $config['bucket'];
        $couchbase->removeBucket($bucket);
        $this->info(sprintf("Couchbase bucket '%s' deleted.", $bucket));
    }
}
