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
            ['name' => 'View Goods In', 'slug' => 'goods-in.read', 'module' => 'goods-in'],
            ['name' => 'Create Goods In', 'slug' => 'goods-in.create', 'module' => 'goods-in'],
            ['name' => 'Update Goods In', 'slug' => 'goods-in.update', 'module' => 'goods-in'],
            ['name' => 'Delete Goods In', 'slug' => 'goods-in.delete', 'module' => 'goods-in'],
            ['name' => 'View Goods Out', 'slug' => 'goods-out.read', 'module' => 'goods-out'],
            ['name' => 'Create Goods Out', 'slug' => 'goods-out.create', 'module' => 'goods-out'],
            ['name' => 'Update Goods Out', 'slug' => 'goods-out.update', 'module' => 'goods-out'],
            ['name' => 'Delete Goods Out', 'slug' => 'goods-out.delete', 'module' => 'goods-out'],
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
