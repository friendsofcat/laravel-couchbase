<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Symfony\Component\Process\Process;

class BucketCreateIndex extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'couchbase:bucket:create-index {--bucket=} {--username=} {--password=}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Create index on bucket to allow performing nql queries";
  
  public function handle () {
    $config = config('database.connections.couchbase');
    $bucket = $this->option('bucket') ?? $config['bucket'];
    $username = $this->option('username') ?? $config['username'];
    $password = $this->option('password') ?? $config['password'];
    
    $process = Process::fromShellCommandline(sprintf('cbq \
      --script="create primary index on %s" \
      -u %s \
      -p %s',
      $bucket, $username, $password));
    $process->setTimeout(null);
    
    $process->run(function ($type, $buffer) {
      $this->displayOutput($buffer);
    });
    
    $this->displayErrors($process);
    
  }
}
