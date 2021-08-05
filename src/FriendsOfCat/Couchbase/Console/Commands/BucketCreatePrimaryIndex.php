<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Illuminate\Support\Facades\DB;
use FriendsOfCat\Couchbase\Connection;

class BucketCreatePrimaryIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:bucket:create-primary-index {--bucket=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create index on bucket to allow performing nql queries';

    public function handle()
    {
        $config = config('database.connections.couchbase');
        $bucket = $this->option('bucket') ?? $config['bucket'];

        /** @var Connection $couchbase */
        $couchbase = DB::connection('couchbase');
        $couchbase->createPrimaryIndex($bucket);
        $this->info(sprintf("Couchbase primary index created for bucket '%s'", $bucket));
    }
}
