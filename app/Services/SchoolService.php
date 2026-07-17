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

            $account = $school->account()->create([
                'name' => 'Admin ' . $school->name,
                'username' => $data['npsn'],
                'email' => $school->email,
                'password' => Hash::make($data['password']),
                'role' => 'school',
                'status' => $school->status,
            ]);

            $account->assignRole('school');

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

            $account = $school->account;

            if ($account) {
                $accountData = [
                    'name' => 'Admin ' . $school->name,
                    'username' => $data['npsn'],
                    'email' => $data['email'] ?? null,
                    'status' => $data['status'],
                    'role' => 'school',
                ];

                if ($account->username !== $data['npsn']) {
                    $accountData['username'] = $data['npsn'];
                }

                if ($account->email !== $data['email']) {
                    $accountData['email'] = $data['email'] ?? null;
                }

                if (!empty($data['password'])) {
                    $accountData['password'] = Hash::make($data['password']);
                }

                if ($account->status !== $data['status']) {
                    $accountData['status'] = $data['status'];
                }

                if (!empty($accountData)) {
                    $account->update($accountData);
                }

                $account->syncRoles(['school']);

            } else {
                $password = !empty($data['password']) ? $data['password'] : $data['npsn'];

                if (!empty($data['password'])) {
                    $account = $school->account()->create([
                        'name' => 'Admin ' . $school->name,
                        'username' => $data['npsn'],
                        'email' => $school->email,
                        'password' => Hash::make($password),
                        'role' => 'school',
                        'status' => $school->status,
                    ]);

                    $account->assignRole('school');
                }
            }

            return $school;
        });
    }
}