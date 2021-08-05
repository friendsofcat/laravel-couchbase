<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

class ConnectionTest extends TestCase
{
    public function testConnection()
    {
        $connection = DB::connection('couchbase');
        $this->assertInstanceOf(\FriendsOfCat\Couchbase\Connection::class, $connection);
    }

    public function testReconnect()
    {
        /** @var \FriendsOfCat\Couchbase\Connection $c1 */
        /** @var \FriendsOfCat\Couchbase\Connection $c2 */
        $c1 = DB::connection('couchbase');
        $hash1 = spl_object_hash($c1->getCluster());
        $c2 = DB::connection('couchbase');
        $hash2 = spl_object_hash($c2->getCluster());
        $this->assertEquals(spl_object_hash($c1), spl_object_hash($c2));
        $this->assertEquals($hash1, $hash2);
    }

    public function testConnectionSingleton()
    {
        /** @var \FriendsOfCat\Couchbase\Connection $c1 */
        $c1 = DB::connection('couchbase');
        /** @var \FriendsOfCat\Couchbase\Connection $c2 */
        $c2 = DB::connection('couchbase');
        $this->assertEquals(spl_object_hash($c1), spl_object_hash($c2));
        $this->assertEquals(spl_object_hash($c1->getCluster()), spl_object_hash($c2->getCluster()));
    }

    public function testDb()
    {
        $connection = DB::connection('couchbase');
        $this->assertInstanceOf('Couchbase\Bucket', $connection->getBucket());
        $this->assertInstanceOf('Couchbase\Cluster', $connection->getCluster());
    }

    public function testBucketWithTypes()
    {
        $connection = DB::connection();
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->builder('unittests'));
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->table('unittests'));
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->type('unittests'));

        $connection = DB::connection('couchbase');
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->builder('unittests'));
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->table('unittests'));
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->type('unittests'));

        $connection = DB::connection('couchbase');
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->builder('unittests'));
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->table('unittests'));
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $connection->type('unittests'));
    }

    public function testQueryLog()
    {
        DB::enableQueryLog();

        $this->assertEquals(0, count(DB::getQueryLog()));

        DB::type('items')->get();
        $this->assertEquals(1, count(DB::getQueryLog()));

        DB::type('items')->count();
        $this->assertEquals(2, count(DB::getQueryLog()));

        DB::type('items')->where('name', 'test')->update(['name' => 'test']);
        $this->assertEquals(3, count(DB::getQueryLog()));

        DB::type('items')->where('name', 'test')->delete();
        $this->assertEquals(4, count(DB::getQueryLog()));

        DB::type('items')->insert(['name' => 'test']);
        $this->assertEquals(4, count(DB::getQueryLog())); // insert does not use N1QL-queries
    }

    public function testDriverName()
    {
        $driver = DB::connection()->getDriverName();
        $this->assertEquals('couchbase', $driver);

        $driver = DB::connection('couchbase')->getDriverName();
        $this->assertEquals('couchbase', $driver);

        $driver = DB::connection('couchbase')->getDriverName();
        $this->assertEquals('couchbase', $driver);
    }
}
