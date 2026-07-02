<?php

use App\Models\ProcurementSignatory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(\Tests\TestCase::class, RefreshDatabase::class);

test('formattedIdentity mengembalikan format identitas yang benar', function () {
    $signatory = ProcurementSignatory::factory()->make([
        'name' => 'Ahmad Sudirman',
        'nip' => '198001012005011001',
        'role' => 'Pejabat Pembuat Komitmen',
    ]);

    $expectedFormat = 'Ahmad Sudirman (NIP. 198001012005011001)';

    expect($signatory->formattedIdentity())->toEqual($expectedFormat);
});