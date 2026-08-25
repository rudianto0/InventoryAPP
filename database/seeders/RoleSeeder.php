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
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Full access to all application features'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Manage users, stock-in, stock-out, and stock'],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Manage stock-in, stock-out, and view stock'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'View stock-in, stock-out, stock, and users'],
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
        // Admin: all except users.delete & roles.*
        Role::where('slug', 'admin')->first()?->permissions()->sync(
            Permission::where('slug', '!=', 'users.delete')
                ->where('module', '!=', 'roles')
                ->pluck('id')
                ->all()
        );

        // Staff: stock-in.*, stock-out.*, stock.read
        Role::where('slug', 'staff')->first()?->permissions()->sync(
            Permission::whereIn('module', ['stock-in', 'stock-out'])
                ->orWhere('slug', 'stock.read')
                ->pluck('id')
                ->all()
        );

        // Manager: stock-in.read, stock-out.read, stock.read, users.read
        Role::where('slug', 'manager')->first()?->permissions()->sync(
            Permission::whereIn('slug', ['stock-in.read', 'stock-out.read', 'stock.read', 'users.read'])
                ->pluck('id')
                ->all()
        );
    }
}
