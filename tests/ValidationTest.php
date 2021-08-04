<?php

namespace Tests;

use Illuminate\Support\Facades\Validator;
use Tests\Models\User;

class ValidationTest extends TestCase {
  
  use RefreshDatabase;

    public function testUnique() {
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
