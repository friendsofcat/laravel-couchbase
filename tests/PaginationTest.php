<?php

namespace Tests;

use Tests\Models\User;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function testAll()
    {
        User::create(['name' => 'John Doe 1', 'abc' => 1]);
        User::create(['name' => 'John Doe 2', 'abc' => 1]);
        User::create(['name' => 'John Doe 3', 'abc' => 2]);
        User::create(['name' => 'John Doe 4', 'abc' => 2]);
        User::create(['name' => 'John Doe 5', 'abc' => 2]);
        User::create(['name' => 'John Doe 6', 'abc' => 2]);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $pagination */
        $pagination = User::paginate(2);
        dd($pagination, User::count(), User::all());
        $this->assertEquals(2, $pagination->count());
        $this->assertEquals(6, $pagination->total());
    }

    public function testWhere()
    {
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 1', 'abc' => 1]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 2', 'abc' => 1]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 3', 'abc' => 2]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 4', 'abc' => 2]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 5', 'abc' => 2]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 6', 'abc' => 2]);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $pagination */
        $pagination = User::where('abc', 2)->paginate(2);
        $this->assertEquals(2, $pagination->count());
        $this->assertEquals(4, $pagination->total());
    }

    public function testOrderBy()
    {
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 1', 'abc' => 1]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 2', 'abc' => 1]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 3', 'abc' => 2]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 4', 'abc' => 2]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 5', 'abc' => 2]);
        $this->getCouchbaseConnection()->table('users')->insert(['name' => 'John Doe 6', 'abc' => 2]);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $pagination */
        $pagination = User::where('abc', 2)->orderBy('name', 'ASC')->paginate(2);
        $this->assertEquals(2, $pagination->count());
        $this->assertEquals(4, $pagination->total());
        $this->assertEquals('John Doe 3', $pagination->get(0)->name);
        $this->assertEquals('John Doe 4', $pagination->get(1)->name);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $pagination */
        $pagination = User::where('abc', 2)->orderBy('name', 'ASC')->paginate(2, ['*'], 'page', 2);
        $this->assertEquals(2, $pagination->count());
        $this->assertEquals(4, $pagination->total());
        $this->assertEquals('John Doe 5', $pagination->get(0)->name);
        $this->assertEquals('John Doe 6', $pagination->get(1)->name);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $pagination */
        $pagination = User::where('abc', 2)->orderBy('name', 'DESC')->paginate(2);
        $this->assertEquals(2, $pagination->count());
        $this->assertEquals(4, $pagination->total());
        $this->assertEquals('John Doe 6', $pagination->get(0)->name);
        $this->assertEquals('John Doe 5', $pagination->get(1)->name);
    }
}
