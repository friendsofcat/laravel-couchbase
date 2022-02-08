<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Illuminate\Support\Facades\DB;
use FriendsOfCat\Couchbase\Connection;

class BucketCreateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:bucket:create-index {--query= : N1QL query for creating the index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a targerted index on bucket to allow performing N1QL queries';

    public function handle()
    {
        $config = config('database.connections.couchbase');

        if (! $query = $this->option('query')) {
            $this->error('The --query parameter is required to create an index');
        }

        /** @var Connection $couchbase */
        $couchbase = DB::connection('couchbase');
        $couchbase->createIndex($query);
        $this->info('Couchbase index created');
    }
}
