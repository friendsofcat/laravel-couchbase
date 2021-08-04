<?php


namespace Tests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\Models\User;

class AuthTest extends TestCase {

    public function testAuthAttempt() {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => Hash::make('foobar'),
        ]);

        $this->assertTrue(Auth::attempt(['email' => 'john@doe.com', 'password' => 'foobar'], true));
        $this->assertTrue(Auth::check());
    }
}
