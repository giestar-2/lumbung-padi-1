<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class SetupOwner extends Command
{
    protected $signature = 'app:setup-owner';

    protected $description = 'Buat akun owner sekali, dengan password yang dimasukkan secara tersembunyi';

    public function handle(): int
    {
        if (User::exists()) {
            $this->error('Akun sudah tersedia. Setup tidak mengubah akun atau password yang ada.');

            return self::FAILURE;
        }
        $data = ['name' => $this->ask('Nama owner'), 'email' => $this->ask('Email'), 'password' => $this->secret('Password (minimal 12 karakter)')];
        $validator = Validator::make($data, ['name' => 'required|string|max:150', 'email' => 'required|email|max:255', 'password' => 'required|string|min:12']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        User::create($data);
        $this->info('Akun owner dibuat.');

        return self::SUCCESS;
    }
}
