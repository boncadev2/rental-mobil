<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['name' => 'Super Admin', 'slug' => 'super-admin'], ['name' => 'Admin', 'slug' => 'admin'], ['name' => 'Staff', 'slug' => 'staff'], ['name' => 'Customer', 'slug' => 'customer']] as $role) {
            Role::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
