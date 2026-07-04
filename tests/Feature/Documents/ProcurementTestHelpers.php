<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\ProcurementSignatory;
use App\Models\SchoolSetting;
use App\Models\Supplier;
use App\Models\User;

/**
 *
 * @param  array  $overrides
 */
function readyProcurement(array $overrides = []): ProcurementRequest
{
    $procurement = ProcurementRequest::factory()->create(array_merge([
        'status' => ProcurementRequest::STATUS_SUPPLIER_ASSIGNED,
        'supplier_id' => Supplier::factory(),
        'is_taxable' => true,
        'ppn_rate' => 11.00,
        'pph_22_rate' => 1.50,
        'pph_23_rate' => 2.00,
    ], $overrides));

    if ($procurement->school && !$procurement->school->setting) {
        SchoolSetting::factory()->create(['school_id' => $procurement->school_id]);
    }

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'line_number' => 1,
        'item_name' => 'Kertas HVS A4 80gr',
        'unit' => 'Rim',
        'quantity' => 100,
        'estimated_price' => 55000,
        'official_price' => 50000,
        'is_pph' => true,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'line_number' => 2,
        'item_name' => 'Tinta Printer Hitam',
        'unit' => 'Botol',
        'quantity' => 20,
        'estimated_price' => 120000,
        'official_price' => 110000,
        'is_pph' => false,
    ]);

    foreach (['headmaster', 'inspector', 'treasurer'] as $role) {
        ProcurementSignatory::factory()->create([
            'procurement_request_id' => $procurement->id,
            'role' => $role,
            'name' => ucfirst($role) . ' Test',
            'nip' => '19800101 200501 1 001',
        ]);
    }

    return $procurement->fresh(['school.setting', 'supplier', 'items', 'signatories', 'documents']);
}

function makeUser(): User
{
    return User::factory()->create();
}
