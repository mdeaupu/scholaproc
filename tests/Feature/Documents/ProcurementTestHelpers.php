<?php

use App\Models\ItemUnit;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\SchoolSetting;
use App\Models\Subsidiary;
use App\Models\Supplier;
use App\Models\User;

function readyProcurement(array $overrides = []): ProcurementRequest
{
    $subsidiary = Subsidiary::factory()->create();
    $admin = User::factory()->admin()->create();

    $procurement = ProcurementRequest::factory()->create(array_merge([
        'status' => ProcurementRequest::STATUS_SUBMITTED,
        'supplier_id' => Supplier::factory(),
        'subsidiary_id' => $subsidiary->id,
        'is_taxable' => true,
        'ppn_rate' => 11.00,
        'pph_22_rate' => 1.50,
        'pph_23_rate' => 2.00,
        'tax_configuration_validated' => true,
        'tax_configuration_basis' => 'basis uji',
        'tax_configuration_validated_by' => $admin->id,
        'tax_configuration_validated_at' => now(),
    ], $overrides));

    if ($procurement->school && ! $procurement->school->setting) {
        SchoolSetting::factory()->create(['school_id' => $procurement->school_id]);
    }

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'line_number' => 1,
        'item_name' => 'Kertas HVS A4 80gr',
        'unit_id' => ItemUnit::factory(),
        'quantity' => 100,
        'estimated_price' => 55000,
        'official_price' => 50000,
        'is_pph' => true,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'line_number' => 2,
        'item_name' => 'Tinta Printer Hitam',
        'unit_id' => ItemUnit::factory(),
        'quantity' => 20,
        'estimated_price' => 120000,
        'official_price' => 110000,
        'is_pph' => false,
    ]);

    $procurement->signatories()->createMany(collect(['headmaster', 'inspector', 'treasurer'])->map(fn ($role) => [
        'procurement_request_id' => $procurement->id,
        'role' => $role,
        'name' => ucfirst($role).' Test',
        'nip' => '19800101 200501 1 001',
    ])->all());

    return $procurement->fresh(['school.setting', 'supplier', 'items', 'signatories', 'documents']);
}

function makeUser(): User
{
    return User::factory()->create();
}
