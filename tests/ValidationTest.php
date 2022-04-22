<?php

namespace Tests;

use Tests\Models\User;
use Illuminate\Support\Facades\Validator;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    public function testUnique()
    {
        $validator = Validator::make(
            ['name' => 'John Doe'],
            ['name' => 'required|unique:couchbase.users']
        );
        $this->assertFalse($validator->fails());

        User::create(['name' => 'John Doe']);

        $validator = Validator::make(
            ['name' => 'John Doe'],
            ['name' => 'required|unique:couchbase.users']
        );
        $this->assertTrue($validator->fails());
    }
}
