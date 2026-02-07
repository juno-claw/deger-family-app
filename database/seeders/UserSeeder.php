<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Olli
        User::create([
            'name' => 'Olli',
            'email' => 'olli@deger.family',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Create Sabsy
        User::create([
            'name' => 'Sabsy',
            'email' => 'sabsy@deger.family',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Create Juno (AI Agent)
        $juno = User::create([
            'name' => 'Juno',
            'email' => 'juno@deger.family',
            'password' => bcrypt(Str::random(32)),
            'role' => 'ai_agent',
        ]);

        // Create Sanctum API token for Juno
        $token = $juno->createToken('juno-api-token');

        $this->command->info("Juno API Token: {$token->plainTextToken}");
    }
}
