<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus cache permission (kalau ada)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ======================
        // 1️⃣ Buat Permissions
        // ======================

        $permissions = [
            // SPH menu
            'sph.create',
            'sph.view',
            'sph.approve',
            'sph.reject',
            'sph.delete',
            // Tambah menu lain di sini kalau ada
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ======================
        // 2️⃣ Buat Roles
        // ======================

        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $direktur   = Role::firstOrCreate(['name' => 'direktur']);

        // ======================
        // 3️⃣ Assign Permissions ke Roles
        // ======================

        // Superadmin dapat SEMUA permission
        $superadmin->syncPermissions(Permission::all());

        // Direktur hanya SPH
        $direktur->syncPermissions([
            'sph.create',
            'sph.view',
            'sph.approve',
            'sph.reject',
            'sph.delete',
        ]);

        // ======================
        // 4️⃣ Assign Roles ke Users
        // ======================

        // Contoh assign user id=1 jadi superadmin
        $adminUser = User::find(1);
        if ($adminUser) {
            $adminUser->assignRole('superadmin');
        }
    }
}