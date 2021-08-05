<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Symfony\Component\Process\Process;

class ClusterSet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couchbase:cluster:set
                            {--cluster-ram=2048}
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
        // couchbase-cli setting-cluster --cluster-ramsize 1024 -c 127.0.0.1 -u Administrator -p password
        $config = config('database.connections.couchbase');
        $host = $this->option('host') ?? $config['host'];
        $username = $this->option('username') ?? $config['username'];
        $password = $this->option('password') ?? $config['password'];
        $clusterRam = $this->option('cluster-ram') ?? 2048;
        $clusterIndexRam = $this->option('cluster-index-ram') ?? 256;
        $process = Process::fromShellCommandline(sprintf(
            'couchbase-cli setting-cluster \
      -c %s \
      -u %s \
      -p %s \
      --cluster-ramsize %s \
      --cluster-index-ramsize %s',
            $host,
            $username,
            $password,
            $clusterRam,
            $clusterIndexRam
        ));
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            $this->displayOutput($buffer);
        });

        $this->displayErrors($process);
    }
}
