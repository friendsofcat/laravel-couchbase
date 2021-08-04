<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Symfony\Component\Process\Process;

class BucketList extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'couchbase:bucket:list {--username=} {--password=} {--host=}';
  
  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'List available buckets on cluster';
  
  public function handle () {
    $config = config('database.connections.couchbase');
    $host = $this->option('host') ?? $config['host'];
    $username = $this->option('username') ?? $config['username'];
    $password = $this->option('password') ?? $config['password'];
    
    $process = Process::fromShellCommandline(sprintf('couchbase-cli bucket-list -u %s -p %s -c %s',
      $username, $password, $host));
    $process->setTimeout(null);
    
    $process->run(function ($type, $buffer) {
      $this->displayOutput($buffer);
    });
    
    $this->displayErrors($process);
    
  }
}
