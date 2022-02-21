<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use FriendsOfCat\Couchbase\Connection;

class BucketRunQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:bucket:run-query {--query= : Raw N1QL query to run}';

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
            $this->error('The --query parameter is required to run a query');
        }

        /** @var Connection $couchbase */
        $couchbase = DB::connection('couchbase');
        $this->info(print_r($couchbase->runRawQuery($query), true));

        return Command::SUCCESS;
    }
}
