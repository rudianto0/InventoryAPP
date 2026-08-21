<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Akses penuh ke semua fitur aplikasi'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Mengelola user, goods-in, goods-out, dan stock'],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Mengelola goods-in, goods-out, dan melihat stock'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Melihat goods-in, goods-out, stock, dan user'],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $this->attachPermissions();
    }

    /**
     * Attach permissions to non-super-admin roles.
     */
    private function attachPermissions(): void
    {
        // Admin: semua kecuali users.delete & roles.*
        Role::where('slug', 'admin')->first()?->permissions()->sync(
            Permission::where('slug', '!=', 'users.delete')
                ->where('module', '!=', 'roles')
                ->pluck('id')
                ->all()
        );

        // Staff: goods-in.*, goods-out.*, stock.read
        Role::where('slug', 'staff')->first()?->permissions()->sync(
            Permission::whereIn('module', ['goods-in', 'goods-out'])
                ->orWhere('slug', 'stock.read')
                ->pluck('id')
                ->all()
        );

        // Manager: goods-in.read, goods-out.read, stock.read, users.read
        Role::where('slug', 'manager')->first()?->permissions()->sync(
            Permission::whereIn('slug', ['goods-in.read', 'goods-out.read', 'stock.read', 'users.read'])
                ->pluck('id')
                ->all()
        );
    }
}
