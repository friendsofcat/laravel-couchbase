<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Symfony\Component\Process\Process;

class ClusterInit extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'couchbase:cluster:init {--username=} {--password=} {--host=} {--cluster-ram=} {--cluster-index-ram=}';
  
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
    $host = $this->option('host') ?? $config['host'];
    $username = $this->option('username') ?? $config['username'];
    $password = $this->option('password') ?? $config['password'];
    $clusterRam = $this->option('cluster-ram') ?? 1024;
    $clusterIndexRam = $this->option('cluster-index-ram') ?? 256;
    $process = Process::fromShellCommandline(sprintf('couchbase-cli cluster-init -c %s
      --cluster-username %s \
      --cluster-password %s \
      --services data,index,query \
      --cluster-ramsize %s \
      --cluster-index-ramsize %s',
      $host, $username, $password, $clusterRam, $clusterIndexRam));
    $process->setTimeout(null);
  
    $process->run(function ($type, $buffer) {
      $this->displayOutput($buffer);
    });
  
    $this->displayErrors($process);
  }
  
}
