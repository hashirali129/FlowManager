<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'create request',
            'approve leave',
            'approve medical',
            'approve travel',
            'view assigned requests',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $hr = Role::firstOrCreate(['name' => 'hr']);

        $admin->givePermissionTo(Permission::all());

        $employee->givePermissionTo(['create request']);

        $manager->givePermissionTo([
            'approve leave',
            'approve medical',
            'approve travel',
            'view assigned requests',
        ]);

        $hr->givePermissionTo([
            'approve leave',
            'approve medical',
            'approve travel',
            'view assigned requests',
        ]);
    }
}
