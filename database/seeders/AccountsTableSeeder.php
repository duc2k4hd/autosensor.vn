<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accounts')->upsert([
            [
                'id' => 1,
                'name' => 'Nguyễn Minh Đức ❤️',
                'email' => 'admin@gmail.com',
                'phone' => '0827786198',
                'email_verified_at' => '2025-11-29 07:28:00',
                'password' => bcrypt('ducnobi2004'),
                'role' => 'admin',
                'remember_token' => 'tFRXJfZ6dKOB6zdFowQYmxzIoFF3AiRncgJ1acGmdopwvWMDDwGQjqHlpKzE',
                'last_password_changed_at' => '2025-11-30 01:15:20',
                'login_attempts' => 0,
                'status' => 'active',
                'admin_note' => null,
                'tags' => null,
                'security_flags' => null,
                'login_history' => null,
                'logs' => null,
                'created_at' => '2025-11-29 07:28:00',
                'updated_at' => '2025-11-30 01:15:21',
                'deleted_at' => null,
            ],
        ], ['id']);
    }
}
