<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleSchool = Role::firstOrCreate(['name' => 'school']);

        $school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Procurement',
            'address' => 'Jl. Teknik Informatika No. 45, Kota Dev',
            'postal_code' => '40123',
            'phone_number' => '0217654321',
            'email' => 'smkn1@sch.id',
            'status' => 'active',
        ]);

        $superadmin = User::create([
            'username' => 'superadmin',
            'name' => 'Ahmad SuperAdmin (Owner)',
            'email' => 'superadmin@scholaproc.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'role' => 'superadmin',
        ]);
        $superadmin->assignRole($roleSuperAdmin);

        $adminCv = User::create([
            'username' => 'admin',
            'name' => 'Budi Admin CV',
            'email' => 'admin.cv@scholaproc.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);
        $adminCv->assignRole($roleAdmin);

        $schoolUser = User::create([
            'school_id' => $school->id,
            'username' => 'school',
            'name' => 'Siti Operator Sekolah',
            'email' => 'siti.school@scholaproc.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'role' => 'school',
        ]);
        $schoolUser->assignRole($roleSchool);
    }
}
