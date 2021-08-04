<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Symfony\Component\Process\Process;

class BucketCreate extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'couchbase:bucket:create {--bucket=} {--username=} {--password=} {--host=} {--bucket-ram=}';
  
  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create a bucket.';
  
  public function handle () {
    $config = config('database.connections.couchbase');
    $host = $this->option('host') ?? $config['host'];
    $bucket = $this->option('bucket') ?? $config['bucket'];
    $username = $this->option('username') ?? $config['username'];
    $password = $this->option('password') ?? $config['password'];
    $bucketRam = $this->option('bucket-ram') ?? 1024;
    
    $process = Process::fromShellCommandline(sprintf('couchbase-cli bucket-create \
      --bucket-type=couchbase \
      --bucket-ramsize=%s \
      --bucket %s \
      -u %s \
      -p %s \
      -c %s \
      --enable-flush 1',
      $bucketRam, $username, $password, $host, $bucket));
    
    $process->setTimeout(null);
    
    $process->run(function ($type, $buffer) {
      $this->displayOutput($buffer);
    });
    
    $this->displayErrors($process);
    
  }
}
