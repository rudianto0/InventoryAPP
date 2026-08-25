<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'View User', 'slug' => 'users.read', 'module' => 'users'],
            ['name' => 'Create User', 'slug' => 'users.create', 'module' => 'users'],
            ['name' => 'Update User', 'slug' => 'users.update', 'module' => 'users'],
            ['name' => 'Delete User', 'slug' => 'users.delete', 'module' => 'users'],
            ['name' => 'View Role', 'slug' => 'roles.read', 'module' => 'roles'],
            ['name' => 'Create Role', 'slug' => 'roles.create', 'module' => 'roles'],
            ['name' => 'Update Role', 'slug' => 'roles.update', 'module' => 'roles'],
            ['name' => 'Delete Role', 'slug' => 'roles.delete', 'module' => 'roles'],
            ['name' => 'View Stock In', 'slug' => 'stock-in.read', 'module' => 'stock-in'],
            ['name' => 'Create Stock In', 'slug' => 'stock-in.create', 'module' => 'stock-in'],
            ['name' => 'Update Stock In', 'slug' => 'stock-in.update', 'module' => 'stock-in'],
            ['name' => 'Delete Stock In', 'slug' => 'stock-in.delete', 'module' => 'stock-in'],
            ['name' => 'View Stock Out', 'slug' => 'stock-out.read', 'module' => 'stock-out'],
            ['name' => 'Create Stock Out', 'slug' => 'stock-out.create', 'module' => 'stock-out'],
            ['name' => 'Update Stock Out', 'slug' => 'stock-out.update', 'module' => 'stock-out'],
            ['name' => 'Delete Stock Out', 'slug' => 'stock-out.delete', 'module' => 'stock-out'],
            ['name' => 'View Stock', 'slug' => 'stock.read', 'module' => 'stock'],
            ['name' => 'Update Stock', 'slug' => 'stock.update', 'module' => 'stock'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
