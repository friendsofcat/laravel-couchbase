<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Illuminate\Support\Facades\DB;
use FriendsOfCat\Couchbase\Connection;

class BucketList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:bucket:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available buckets on cluster';

    public function handle()
    {
        /** @var Connection $couchbase */
        $couchbase = DB::connection('couchbase');
        $buckets = $couchbase->listBuckets();

        if (count($buckets) == 0) {
            $this->comment('No bucket found.');

            return self::SUCCESS;
        }

        foreach ($buckets as $bucket) {
            $this->info(sprintf(
                'Bucket: %s, flush enabled: %s, ram: %sMB',
                $bucket->name(),
                $bucket->flushEnabled() ? 'yes' : 'no',
                $bucket->ramQuotaMb()
            ));
        }
    }
}
