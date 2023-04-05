<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use FriendsOfCat\Couchbase\Query\Grammar;
use FriendsOfCat\Couchbase\Query\Builder as Query;

class QueryBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function testCollection()
    {
        $this->assertInstanceOf('FriendsOfCat\Couchbase\Query\Builder', $this->getCouchbaseConnection()->table('users'));
    }

    public function testGet()
    {
        $users = $this->getCouchbaseConnection()->table('users')->get();
        $this->assertEquals(0, count($users));

        $this->getCouchbaseConnection()->table('users')->useKeys('users::john_doe')->insert(['name' => 'John Doe']);

        $users = $this->getCouchbaseConnection()->table('users')->get();
        $this->assertEquals(1, count($users));
    }

    public function testNoDocument()
    {
        $items = $this->getCouchbaseConnection()->table('items')->where('name', 'nothing')->get()->toArray();
        $this->assertEquals([], $items);

        $item = $this->getCouchbaseConnection()->table('items')->where('name', 'nothing')->first();
        $this->assertEquals(null, $item);

        $item = $this->getCouchbaseConnection()->table('items')->where('_id', '51c33d8981fec6813e00000a')->first();
        $this->assertEquals(null, $item);
    }

    public function testInsert()
    {
        $this->getCouchbaseConnection()->table('users')->useKeys('users::tags')->insert([
            'tags' => ['tag1', 'tag2'],
            'name' => 'John Doe',
        ]);

        $users = $this->getCouchbaseConnection()->table('users')->get();
        $this->assertEquals(1, count($users));

        $user = $users[0];
        $this->assertEquals('John Doe', $user['name']);
        $this->assertTrue(is_array($user['tags']));
    }

    public function testGetUnderscoreId()
    {
        $id = 'foobar.' . uniqid();
        $this->getCouchbaseConnection()->table('users')->useKeys($id)->insert(['name' => 'John Doe']);
        $this->assertArrayHasKey('_id', $this->getCouchbaseConnection()->table('users')->useKeys($id)->first());
        $this->assertSame($id, $this->getCouchbaseConnection()->table('users')->useKeys($id)->first()['_id']);
    }

    public function testInsertGetId()
    {
        $id = $this->getCouchbaseConnection()->table('users')->insertGetId(['name' => 'John Doe']);
        $this->assertTrue(is_string($id));
        $this->assertSame($id, $this->getCouchbaseConnection()->table('users')->useKeys($id)->first()['_id']);

        $id = $this->getCouchbaseConnection()->table('users')->useKeys('foobar')->insertGetId(['name' => 'John Doe']);
        $this->assertSame('foobar', $id);
        $this->assertSame($id, $this->getCouchbaseConnection()->table('users')->useKeys($id)->first()['_id']);
    }

    public function testBatchInsert()
    {
        $this->getCouchbaseConnection()->table('users')->useKeys('batch')->insert([
            [
                'tags' => ['tag1', 'tag2'],
                'name' => 'Jane Doe',
            ],
            [
                'tags' => ['tag3'],
                'name' => 'John Doe',
            ],
        ]);

        $users = $this->getCouchbaseConnection()->table('users')->get();

        $this->assertEquals(2, count($users));
        $this->assertTrue(is_array($users[0]['tags']));
    }

    public function testFind()
    {
        $id = 'my_id';
        $this->getCouchbaseConnection()->table('users')->useKeys($id)->insert(['name' => 'John Doe']);

        $user = $this->getCouchbaseConnection()->table('users')->find($id);
        $this->assertEquals('John Doe', $user['name']);
    }

    public function testFindNull()
    {
        $user = $this->getCouchbaseConnection()->table('users')->find(null);
        $this->assertEquals(null, $user);
    }

    public function testCount()
    {
        $this->getCouchbaseConnection()->table('users')->useKeys('users.1')->insert(['name' => 'Jane Doe']);
        $this->getCouchbaseConnection()->table('users')->useKeys('users.2')->insert(['name' => 'Jane Doe']);

        $this->assertEquals(2, $this->getCouchbaseConnection()->table('users')->count());
    }

    public function testUpdate()
    {
        $this->getCouchbaseConnection()->table('users')->useKeys('users.1')->insert(['name' => 'John Doe', 'age' => 30]);
        $this->getCouchbaseConnection()->table('users')->useKeys('users.2')->insert(['name' => 'Jane Doe', 'age' => 20]);

        $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->update(['age' => 100]);
        $users = $this->getCouchbaseConnection()->table('users')->get();

        $john = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->first();
        $jane = $this->getCouchbaseConnection()->table('users')->where('name', 'Jane Doe')->first();
        $this->assertEquals(100, $john['age']);
        $this->assertEquals(20, $jane['age']);
    }

    public function testDelete()
    {
        $this->getCouchbaseConnection()->table('users')->useKeys('users.1')->insert(['name' => 'John Doe', 'age' => 25]);
        $this->getCouchbaseConnection()->table('users')->useKeys('users.2')->insert(['name' => 'Jane Doe', 'age' => 20]);

        $this->getCouchbaseConnection()->table('users')->where('age', '<', 10)->delete();
        $this->assertEquals(2, $this->getCouchbaseConnection()->table('users')->count());

        $this->getCouchbaseConnection()->table('users')->where('age', '<', 25)->delete();
        $this->assertEquals(1, $this->getCouchbaseConnection()->table('users')->count());
    }

    public function testTruncate()
    {
        $this->getCouchbaseConnection()->table('users')->useKeys('john')->insert(['name' => 'John Doe']);
        $this->getCouchbaseConnection()->table('users')->truncate();
        $this->assertEquals(0, $this->getCouchbaseConnection()->table('users')->count());
    }

    public function testSubKey()
    {
        $this->getCouchbaseConnection()->table('users')->useKeys('users.1')->insert([
            'name' => 'John Doe',
            'address' => ['country' => 'Belgium', 'city' => 'Ghent'],
        ]);
        $this->getCouchbaseConnection()->table('users')->useKeys('users.2')->insert([
            'name' => 'Jane Doe',
            'address' => ['country' => 'France', 'city' => 'Paris'],
        ]);

        $users = $this->getCouchbaseConnection()->table('users')->where('address.country', 'Belgium')->get();
        $this->assertEquals(1, count($users));
        $this->assertEquals('John Doe', $users[0]['name']);
    }

    public function testInArray()
    {
        $this->getCouchbaseConnection()->table('items')->useKeys('items.1')->insert([
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
        ]);
        $this->getCouchbaseConnection()->table('items')->useKeys('items.2')->insert([
            'tags' => ['tag2'],
        ]);

        $items = $this->getCouchbaseConnection()->table('items')->whereRaw('ARRAY_CONTAINS(tags, "tag2")')->get();
        $this->assertEquals(2, count($items));

        $items = $this->getCouchbaseConnection()->table('items')->whereRaw('ARRAY_CONTAINS(tags, "tag1")')->get();
        $this->assertEquals(1, count($items));
    }

    public function testDistinct()
    {
        $this->getCouchbaseConnection()->table('items')->useKeys('item:1')->insert(['name' => 'knife', 'type' => 'sharp']);
        $this->getCouchbaseConnection()->table('items')->useKeys('item:2')->insert(['name' => 'fork', 'type' => 'sharp']);
        $this->getCouchbaseConnection()->table('items')->useKeys('item:3')->insert(['name' => 'spoon', 'type' => 'round']);
        $this->getCouchbaseConnection()->table('items')->useKeys('item:4')->insert(['name' => 'spoon', 'type' => 'round']);

        $items = $this->getCouchbaseConnection()->table('items')->select('name')->distinct()->get()->toArray();
        sort($items);
        $this->assertEquals(3, count($items));
        $this->assertEquals([['name' => 'fork'], ['name' => 'knife'], ['name' => 'spoon']], $items);

        $types = $this->getCouchbaseConnection()->table('items')->select('type')->distinct()->get()->toArray();
        sort($types);
        $this->assertEquals(2, count($types));
        $this->assertEquals([['type' => 'round'], ['type' => 'sharp']], $types);
    }

    public function testTake()
    {
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'knife', 'type' => 'sharp', 'amount' => 34]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'fork', 'type' => 'sharp', 'amount' => 20]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'spoon', 'type' => 'round', 'amount' => 3]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'spoon', 'type' => 'round', 'amount' => 14]);

        $items = $this->getCouchbaseConnection()->table('items')->orderBy('name')->take(2)->get();
        $this->assertEquals(2, count($items));
        $this->assertEquals('fork', $items[0]['name']);
    }

    public function testSkip()
    {
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'knife', 'type' => 'sharp', 'amount' => 34]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'fork', 'type' => 'sharp', 'amount' => 20]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'spoon', 'type' => 'round', 'amount' => 3]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'spoon', 'type' => 'round', 'amount' => 14]);

        $items = $this->getCouchbaseConnection()->table('items')->orderBy('name')->skip(2)->get();
        $this->assertEquals(2, count($items));
        $this->assertEquals('spoon', $items[0]['name']);
    }

    public function testPluck()
    {
        $this->getCouchbaseConnection()->table('users')->useKeys('user.1')->insert(['name' => 'Jane Doe', 'age' => 20]);
        $this->getCouchbaseConnection()->table('users')->useKeys('user.2')->insert(['name' => 'John Doe', 'age' => 25]);

        $age = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->pluck('age')->toArray();
        $this->assertEquals([25], $age);
    }

    public function testList()
    {
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'knife', 'type' => 'sharp', 'amount' => 34]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'fork', 'type' => 'sharp', 'amount' => 20]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'spoon', 'type' => 'round', 'amount' => 3]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'spoon', 'type' => 'round', 'amount' => 14]);

        $list = $this->getCouchbaseConnection()->table('items')->pluck('name')->toArray();
        sort($list);
        $this->assertEquals(4, count($list));
        $this->assertEquals(['fork', 'knife', 'spoon', 'spoon'], $list);

        $list = $this->getCouchbaseConnection()->table('items')->pluck('type', 'name')->toArray();
        $this->assertEquals(3, count($list));
        $this->assertEquals(['knife' => 'sharp', 'fork' => 'sharp', 'spoon' => 'round'], $list);

        $list = $this->getCouchbaseConnection()->table('items')->pluck('name', '_id')->toArray();
        $this->assertEquals(4, count($list));
        $this->assertEquals(18, strlen(key($list)));
    }

    public function testAggregate()
    {
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'knife', 'type' => 'sharp', 'amount' => 34]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'fork', 'type' => 'sharp', 'amount' => 20]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'spoon', 'type' => 'round', 'amount' => 3]);
        $this->getCouchbaseConnection()->table('items')->useKeys('item.' . uniqid())->insert(['name' => 'spoon', 'type' => 'round', 'amount' => 14]);

        $this->assertEquals(71, $this->getCouchbaseConnection()->table('items')->sum('amount'));
        $this->assertEquals(4, $this->getCouchbaseConnection()->table('items')->count('amount'));
        $this->assertEquals(3, $this->getCouchbaseConnection()->table('items')->min('amount'));
        $this->assertEquals(34, $this->getCouchbaseConnection()->table('items')->max('amount'));
        $this->assertEquals(17.75, $this->getCouchbaseConnection()->table('items')->avg('amount'));

        $this->assertEquals(2, $this->getCouchbaseConnection()->table('items')->where('name', 'spoon')->count('amount'));
        $this->assertEquals(14, $this->getCouchbaseConnection()->table('items')->where('name', 'spoon')->max('amount'));
    }

    public function testSubDocumentAggregate()
    {
        $this->getCouchbaseConnection()->table('items')->insert([
            ['name' => 'knife', 'amount' => ['hidden' => 10, 'found' => 3]],
            ['name' => 'fork', 'amount' => ['hidden' => 35, 'found' => 12]],
            ['name' => 'spoon', 'amount' => ['hidden' => 14, 'found' => 21]],
            ['name' => 'spoon', 'amount' => ['hidden' => 6, 'found' => 4]],
        ]);

        $this->assertEquals(65, $this->getCouchbaseConnection()->table('items')->sum('amount.hidden'));
        $this->assertEquals(4, $this->getCouchbaseConnection()->table('items')->count('amount.hidden'));
        $this->assertEquals(6, $this->getCouchbaseConnection()->table('items')->min('amount.hidden'));
        $this->assertEquals(35, $this->getCouchbaseConnection()->table('items')->max('amount.hidden'));
        $this->assertEquals(16.25, $this->getCouchbaseConnection()->table('items')->avg('amount.hidden'));
    }

    public function testUnset()
    {
        $id1 = $this->getCouchbaseConnection()->table('users')->insertGetId(['name' => 'John Doe', 'note1' => 'ABC', 'note2' => 'DEF']);
        $id2 = $this->getCouchbaseConnection()->table('users')->insertGetId(['name' => 'Jane Doe', 'note1' => 'ABC', 'note2' => 'DEF']);

        $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->unset('note1');

        $user1 = $this->getCouchbaseConnection()->table('users')->find($id1);
        $user2 = $this->getCouchbaseConnection()->table('users')->find($id2);

        $this->assertFalse(isset($user1['note1']));
        $this->assertTrue(isset($user1['note2']));
        $this->assertTrue(isset($user2['note1']));
        $this->assertTrue(isset($user2['note2']));

        $this->getCouchbaseConnection()->table('users')->where('name', 'Jane Doe')->unset(['note1', 'note2']);

        $user2 = $this->getCouchbaseConnection()->table('users')->find($id2);
        $this->assertFalse(isset($user2['note1']));
        $this->assertFalse(isset($user2['note2']));
    }

    public function testUpdateSubDocument()
    {
        $id = $this->getCouchbaseConnection()->table('users')->insertGetId(['name' => 'John Doe', 'address' => ['country' => 'Belgium']]);

        $this->getCouchbaseConnection()->table('users')->useKeys($id)->update(['address.country' => 'England']);

        $check = $this->getCouchbaseConnection()->table('users')->find($id);
        $this->assertEquals('England', $check['address']['country']);
    }

    public function testIncrement()
    {
        $this->getCouchbaseConnection()->table('users')->insert([
            ['name' => 'John Doe', 'age' => 30, 'note' => 'adult'],
            ['name' => 'Jane Doe', 'age' => 10, 'note' => 'minor'],
            ['name' => 'Robert Roe', 'age' => null],
            ['name' => 'Mark Moe'],
        ]);

        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->first();
        $this->assertEquals(30, $user['age']);

        $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->increment('age');
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->first();
        $this->assertEquals(31, $user['age']);

        $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->decrement('age');
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->first();
        $this->assertEquals(30, $user['age']);

        $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->increment('age', 5);
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->first();
        $this->assertEquals(35, $user['age']);

        $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->decrement('age', 5);
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->first();
        $this->assertEquals(30, $user['age']);

        $this->getCouchbaseConnection()->table('users')->where('name', 'Jane Doe')->increment('age', 10, ['note' => 'adult']);
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'Jane Doe')->first();
        $this->assertEquals(20, $user['age']);
        $this->assertEquals('adult', $user['note']);

        $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->decrement('age', 20, ['note' => 'minor']);
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->first();
        $this->assertEquals(10, $user['age']);
        $this->assertEquals('minor', $user['note']);

        $this->getCouchbaseConnection()->table('users')->increment('age');
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'John Doe')->first();
        $this->assertEquals(11, $user['age']);
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'Jane Doe')->first();
        $this->assertEquals(21, $user['age']);
        $user = $this->getCouchbaseConnection()->table('users')->where('name', 'Robert Roe')->first();
        $this->assertEquals(null, $user['age']);
    }

    public function testWhere()
    {
        /** @var Query $query */
        $query = $this->getCouchbaseConnection()->table('table1')->where('a', '=', 'b');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table1" and `a` = "b"',
            $this->queryToSql($query)
        );
    }

    public function testWhereWithTwoParameters()
    {
        /** @var Query $query */
        $query = $this->getCouchbaseConnection()->table('table2')->where('a', 'b');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table2" and `a` = "b"',
            $this->queryToSql($query)
        );
    }

    public function testNestedWhere()
    {
        /** @var Query $query */
        $query = $this->getCouchbaseConnection()->table('table3')->where(function (Query $query) {
            $query->where('a', 'b');
        });
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table3" and (`a` = "b")',
            $this->queryToSql($query)
        );
    }

    public function testDictWhere()
    {
        /** @var Query $query */
        $query = $this->getCouchbaseConnection()->table('table4')->where(['a' => 'b']);
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table4" and (`a` = "b")',
            $this->queryToSql($query)
        );
        $query = $this->getCouchbaseConnection()->table('table5')->where(['a' => 'b', 'c' => 'd']);
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table5" and (`a` = "b" and `c` = "d")',
            $this->queryToSql($query)
        );
    }

    public function testWhereColumnPreservedWord()
    {
        /** @var Query $query */
        $query = $this->getCouchbaseConnection()->table('table6')->where('password', 'foobar');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and `password` = "foobar"',
            $this->queryToSql($query)
        );
    }

    public function testWhereEscapedColumn()
    {
        /** @var Query $query */
        $query = $this->getCouchbaseConnection()->table('table6')->where('`foo`', 'bar');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and `foo` = "bar"',
            $this->queryToSql($query)
        );
    }

    public function testWhereEscapedColumnWithBacktick()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->where('`foo`bar`', 'bar');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and `foo``bar` = "bar"',
            $this->queryToSql($query)
        );
    }

    public function testWhereColumnWithBacktickEnd()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->where('foo`', 'bar');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and `foo``` = "bar"',
            $this->queryToSql($query)
        );
    }

    public function testWhereColumnWithBacktickInside()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->where('foo`bar', 'bar');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and `foo``bar` = "bar"',
            $this->queryToSql($query)
        );
    }

    public function testWhereColumnWithBacktickBeginning()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->where('`foo', 'bar');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and ```foo` = "bar"',
            $this->queryToSql($query)
        );
    }

    public function testWhereColumnWithBrackets()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->where('foo(abc)', 'foobar');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and `foo(abc)` = "foobar"',
            $this->queryToSql($query)
        );
    }

    public function testWhereColumnUnderscoreId()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->where('_id', 'foobar');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and meta(`' . $query->from . '`).`id` = "foobar"',
            $this->queryToSql($query)
        );
    }

    public function testWhereNestedRaw()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->where(function ($query) {
            $query->whereRaw('meta().id = "abc"')
                ->orWhere(DB::raw('substr(`a`, 0, 3)'), 'def');
        });
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and (meta().id = "abc" or substr(`a`, 0, 3) = "def")',
            $this->queryToSql($query)
        );
    }

    public function testWhereDeepNestedRaw()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->where(function ($query) {
            $query->whereRaw('meta().id = "abc"')
                ->orWhere(function ($query) {
                    $query->whereRaw('substr(`b`, 0, 3) = "ghi"')
                        ->where(DB::raw('substr(`a`, 0, 3)'), 'def');
                });
        });
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and (meta().id = "abc" or (substr(`b`, 0, 3) = "ghi" and substr(`a`, 0, 3) = "def"))',
            $this->queryToSql($query)
        );
    }

    public function testSelectColumnWithAs()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->select('foo as bar');
        $this->assertEquals(
            'select `foo` as `bar` from `' . $query->from . '` where `eloquent_type` = "table6"',
            $this->queryToSql($query)
        );
    }

    public function testSelectColumnMetaId()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->select(DB::raw('meta().id as _id'));
        $this->assertEquals(
            'select meta().id as _id from `' . $query->from . '` where `eloquent_type` = "table6"',
            $this->queryToSql($query)
        );
    }

    public function testSelectColumnUnderscoreId()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->select('_id');
        $this->assertEquals(
            'select meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6"',
            $this->queryToSql($query)
        );
    }

    public function testSelectColumnStar()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->select();
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6"',
            $this->queryToSql($query)
        );

        $query = $this->getCouchbaseConnection()->table('table6')->select('*');
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6"',
            $this->queryToSql($query)
        );

        $query = $this->getCouchbaseConnection()->table('table6')->select(['*']);
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6"',
            $this->queryToSql($query)
        );
    }

    public function testUseIndex()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->useIndex('test-index')->select();
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` USE INDEX (`test-index` USING GSI) where `eloquent_type` = "table6"',
            $this->queryToSql($query)
        );
    }

    public function testWhereAnyIn()
    {
        $query = $this->getCouchbaseConnection()->table('table6')->whereAnyIn('user_ids', ['123', '456']);
        $sql = $this->queryToSql($query);
        $this->assertEquals(1, preg_match('/ANY `([a-zA-Z0-9]+)`/', $sql, $match));
        $colIdentifier = $match[1];
        $this->assertEquals(
            'select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `eloquent_type` = "table6" and ANY `' . $colIdentifier . '` IN `user_ids` SATISFIES `' . $colIdentifier . '` IN ["123", "456"] END',
            $sql
        );
    }

    private function queryToSql(Query $query)
    {
        return str_replace_array('?', array_map(function ($value) {
            return Grammar::wrapData($value);
        }, $query->getBindings()), $query->toSql());
    }
}
