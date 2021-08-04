<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Symfony\Component\Process\Process;

class BucketFlush extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'couchbase:bucket:flush {--bucket=} {--username=} {--password=} {--host=}';
  
  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Remove all data on bucket (clear database).';
  
  public function handle () {
    $config = config('database.connections.couchbase');
    $host = $this->option('host') ?? $config['host'];
    $bucket = $this->option('bucket') ?? $config['bucket'];
    $username = $this->option('username') ?? $config['username'];
    $password = $this->option('password') ?? $config['password'];
    
    $process = Process::fromShellCommandline(sprintf('couchbase-cli bucket-flush -u %s -p %s -c %s --bucket %s',
      $username, $password, $host, $bucket));
    $process->setTimeout(null);
  
    $process->run(function ($type, $buffer) {
      $this->displayOutput($buffer);
    });
  
    $this->displayErrors($process);
    
  }
}
