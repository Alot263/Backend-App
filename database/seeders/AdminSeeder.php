<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admins')->insert([
            'id' => 1,
            'f_name' => 'Master Admin',
            'l_name' => 'Solivatore',
            'phone' => '0639691493',
            'email' => 'admin@admin.com',
            'image' => 'def.png',
            'password' => bcrypt(12345678),
            'remember_token' =>Str::random(10),
            'created_at'=>now(),
            'updated_at'=>now()
        ]);
    }
}
