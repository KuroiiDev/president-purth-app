<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {email : Email pengguna} {--name= : Nama lengkap admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat akun admin baru dengan password interaktif';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->option('name');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        if (empty($name)) {
            $name = $this->ask('Masukkan nama lengkap admin', 'Administrator');
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if ($existingUser->isAdmin()) {
                $this->warn("User dengan email {$email} sudah berstatus Admin.");

                return self::SUCCESS;
            }

            if ($this->confirm("User dengan email {$email} sudah terdaftar sebagai {$existingUser->role->value}. Tingkatkan menjadi Admin?")) {
                $existingUser->role = Role::Admin;
                $existingUser->save();
                $this->info("User {$email} berhasil ditingkatkan menjadi Admin.");

                return self::SUCCESS;
            }

            return self::FAILURE;
        }

        $password = $this->secret('Masukkan password admin (min. 10 karakter, huruf besar & kecil, angka):');
        $confirmation = $this->secret('Konfirmasi password:');

        if ($password !== $confirmation) {
            $this->error('Konfirmasi password tidak cocok.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => [Password::defaults()]]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->role = Role::Admin;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Admin {$email} ({$name}) berhasil dibuat!");

        return self::SUCCESS;
    }
}
