<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Symfony\Component\Process\Process;

/**
 * Still work in progress
 *
 * Class ClusterInit
 * @package FriendsOfCat\Couchbase\Console\Commands
 */
class ClusterInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:cluster:init
                            {--cluster-ram=1024}
                            {--cluster-index-ram=256}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize couchbase cluster before usage';

    /**
     * Set up local development environment.
     */
    public function handle()
    {
        $config = config('database.connections.couchbase');
        $process = Process::fromShellCommandline(sprintf(
            'couchbase-cli cluster-init -c %s
      --cluster-username %s \
      --cluster-password %s \
      --services data,index,query \
      --cluster-ramsize %s \
      --cluster-index-ramsize %s',
            $config['host'],
            $config['username'],
            $config['password'],
            $this->option('cluster-ram'),
            $this->option('cluster-index-ram')
        ));
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            $this->displayOutput($buffer);
        });

        $this->displayErrors($process);
    }
}
