<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolService
{
    public function create(array $data): School
    {
        return DB::transaction(function () use ($data) {
            $school = School::create([
                'npsn' => $data['npsn'],
                'name' => $data['name'],
                'address' => $data['address'],
                'postal_code' => $data['postal_code'] ?? null,
                'phone_number' => $data['phone_number'],
                'email' => $data['email'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            $school->setting()->create([
                'kop_pusat' => $data['kop_pusat'],
                'kop_provinsi' => $data['kop_provinsi'],
                'kop_sub_wilayah' => $data['kop_sub_wilayah'] ?? null,
            ]);

            $school->users()->create([
                'name' => 'Admin ' . $school->name,
                'username' => $data['npsn'],
                'password' => Hash::make($data['password']),
                'role' => 'admin_school',
            ]);

            return $school;
        });
    }

    public function update(School $school, array $data): School
    {
        return DB::transaction(function () use ($school, $data) {
            $school->update([
                'npsn' => $data['npsn'],
                'name' => $data['name'],
                'address' => $data['address'],
                'postal_code' => $data['postal_code'] ?? null,
                'phone_number' => $data['phone_number'],
                'email' => $data['email'] ?? null,
                'status' => $data['status'],
            ]);

            $school->setting()->updateOrCreate(
                ['school_id' => $school->id],
                [
                    'kop_pusat' => $data['kop_pusat'],
                    'kop_provinsi' => $data['kop_provinsi'],
                    'kop_sub_wilayah' => $data['kop_sub_wilayah'] ?? null,
                ]
            );

            $admin = $school->admin;

            if ($admin) {
                $adminData = [];

                if ($admin->username !== $data['npsn']) {
                    $adminData['username'] = $data['npsn'];
                }

                if (!empty($data['password'])) {
                    $adminData['password'] = Hash::make($data['password']);
                }

                if (!empty($adminData)) {
                    $admin->update($adminData);
                }
            } else {
                if (!empty($data['password'])) {
                    $school->users()->create([
                        'name' => 'Admin ' . $school->name,
                        'username' => $data['npsn'],
                        'password' => Hash::make($data['password']),
                        'role' => 'admin_school',
                    ]);
                }
            }

            return $school;
        });
    }
}