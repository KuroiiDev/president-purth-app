<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Support\RevisionContext;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        app(RevisionContext::class)->withoutRecording(function () {
            $password = env('ADMIN_PASSWORD');
            $generated = false;

            if (empty($password)) {
                $password = Str::password(16);
                $generated = true;
            }

            $user = User::updateOrCreate(
                ['email' => 'admin@president.ac.id'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]
            );
            $user->role = Role::Admin;
            $user->save();

            if ($generated && $this->command) {
                $this->command->warn('Generated Admin Password for admin@president.ac.id: '.$password);
            }

            $this->call([
                HomeSeeder::class,
            ]);
        });
    }
}
