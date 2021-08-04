<?php

namespace FriendsOfCat\Couchbase\Console\Commands;

use Symfony\Component\Process\Process;

class Command extends \Illuminate\Console\Command
{
  protected function displayErrors(Process $process)
  {
    if (! $process->isSuccessful()) {
      $process->run(function ($type, $buffer) {
        $this->displayOutput($buffer);
      });
      
      // $this->displayErrors($process);
    }
  }
  
  protected function displayOutput($buffer)
  {
    $this->comment($buffer);
  }
}
