<?php

namespace Tests;

use Exception;
use Dotenv\Dotenv;
use ErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Application;
use Illuminate\Support\Facades\Event;
use FriendsOfCat\Couchbase\Connection;
use FriendsOfCat\Couchbase\Events\QueryFired;
use FriendsOfCat\Couchbase\CouchbaseServiceProvider;
use FriendsOfCat\Couchbase\Console\Commands\ClusterInit;
use FriendsOfCat\Couchbase\Console\Commands\BucketCreate;
use FriendsOfCat\Couchbase\Console\Commands\BucketCreatePrimaryIndex;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            CouchbaseServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Application::starting(function ($artisan) {
            $artisan->call(ClusterInit::class);
            $artisan->call(BucketCreate::class);
            $artisan->call(BucketCreatePrimaryIndex::class);
        });
    }

    protected function getCouchbaseConnection(): Connection
    {
        /** @var Connection $couchbase */
        $couchbase = DB::connection('couchbase');

        return $couchbase;
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['path.base'] = __DIR__ . '/../src';

        DB::listen(function (\Illuminate\Database\Events\QueryExecuted $sql) use (&$fh) {
            file_put_contents(__DIR__ . '/../sql-log.sql', $sql->sql . ";\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../sql-log.sql', '-- ' . json_encode($sql->bindings) . "\n", FILE_APPEND);
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
            $backtrace = array_slice($backtrace, 5);
            file_put_contents(__DIR__ . '/../sql-log.sql', '-- ' . implode("\n-- ", array_map(function ($trace) {
                return ($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? '') . '() called at [' . ($trace['file'] ?? '') . ':' . ($trace['line'] ?? '') . ']';
            }, $backtrace)) . "\n\n", FILE_APPEND);
        });
    }

    protected function defineEnvironment($app)
    {
        $dotenv = Dotenv::createImmutable(__DIR__, '../.env.testing');
        $dotenv->load();
        $dotenv->required([
            'COUCHBASE_DB_HOST',
            'COUCHBASE_DB_PORT',
            'COUCHBASE_DB_BUCKET',
            'COUCHBASE_DB_USERNAME',
            'COUCHBASE_DB_PASSWORD',
        ]);

        /** @var \Illuminate\Config\Repository $appConfig */
        $appConfig = $app->make('config');

        $appConfig->set('app.key', 'ZsZewWyUJ5FsKp9lMwv4tYbNlegQilM7');

        $appConfig->set('database.default', 'couchbase');
        $appConfig->set('database.connections.couchbase', [
            'driver' => 'couchbase',
            'port' => env('COUCHBASE_DB_PORT', 8091),
            'host' => env('COUCHBASE_DB_HOST', '127.0.0.1'),
            'bucket' => env('COUCHBASE_DB_BUCKET', 'canvas'),
            'username' => env('COUCHBASE_DB_USERNAME', 'Administrator'),
            'password' => env('COUCHBASE_DB_PASSWORD', 'password'),
        ]);

        $appConfig->set('cache.driver', 'array');
    }

    protected function assertEventListenFirst($event, $callback)
    {
        $fired = false;
        $firedEvent = null;

        Event::listen($event, function ($event) use ($callback, &$fired, &$firedEvent) {
            if ($fired) {
                return;
            }

            $firedEvent = $event;

            $fired = true;
        });

        $callback();

        $this->assertTrue($fired);

        return $firedEvent;
    }

    protected function assertQueryFiredEquals($n1ql, $bindings, $callback)
    {
        /** @var QueryFired $event */
        $event = $this->assertEventListenFirst(QueryFired::class, $callback);

        $this->assertEquals($n1ql, $event->getQuery());

        if ($bindings !== null) {
            $this->assertEquals($bindings, $event->getPositionalParams());
        }
    }

    protected function assertSelectSqlEquals($queryBuilder, $n1ql, $bindings = null)
    {
        $this->assertQueryFiredEquals($n1ql, $bindings, function () use ($queryBuilder) {
            $queryBuilder->get();
        });
    }

    /**
     * @param callable $callback
     * @param string $expectedExceptionClass
     */
    public function assertException($callback, $expectedExceptionClass)
    {
        $thrownExceptionClass = null;

        try {
            $callback();
        } catch (Exception $e) {
            $thrownExceptionClass = get_class($e);
        }
        $this->assertEquals(
            $expectedExceptionClass,
            $thrownExceptionClass,
            'Failed to assert that ' . json_encode($thrownExceptionClass) . ' matches expected exception ' . json_encode($expectedExceptionClass) . '.'
        );
    }

    /**
     * @param callable $callback
     * @param int $severity
     * @param null $messageRegex
     */
    public function assertErrorException($callback, $severity, $messageRegex = null)
    {
        $thrownExceptionClass = null;

        try {
            $callback();
        } catch (Exception $e) {
            $thrownExceptionClass = get_class($e);
        }
        $this->assertEquals(
            ErrorException::class,
            $thrownExceptionClass,
            'Failed to assert that ' . json_encode($thrownExceptionClass) . ' is a ErrorException.'
        );
        /** @var ErrorException $e */
        if ($messageRegex !== null) {
            $this->assertTrue(
                preg_match($messageRegex, $e->getMessage()) !== false,
                'Failed to assert that message ' . json_encode($e->getMessage()) . ' matches regex ' . json_encode($messageRegex) . '.'
            );
        }
        $this->assertEquals($severity, $e->getSeverity(), 'Failed to assert that severity matches.');
    }
}
