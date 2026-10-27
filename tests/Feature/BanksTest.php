<?php

use App\Models\User;
use App\Support\Banks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->withoutVite());

it('loads the bank list from the VietQR API and caches it', function () {
    Http::fake([Banks::API_URL => Http::response(['code' => '00', 'data' => [
        ['code' => 'ICB', 'shortName' => 'VietinBank', 'name' => 'Ngân hàng TMCP Công thương Việt Nam', 'bin' => '970415', 'logo' => 'https://cdn.vietqr.io/img/ICB.png'],
        ['code' => 'momo', 'shortName' => 'MoMo', 'name' => 'CTCP Dịch Vụ Di Động Trực Tuyến', 'bin' => '971025', 'logo' => 'https://cdn.vietqr.io/img/momo.png'],
    ]])]);
    Cache::forget('banks.vietqr');

    expect(Banks::all())->toBe(['ICB' => 'VietinBank', 'MOMO' => 'MoMo'])
        ->and(Banks::find('icb')['bin'])->toBe('970415');

    Banks::list();
    Http::assertSentCount(1);
});

it('falls back to the bundled bank list when the API is down', function () {
    Cache::forget('banks.vietqr');

    expect(Banks::find('VCB'))->toMatchArray(['short_name' => 'Vietcombank', 'bin' => '970436'])
        ->and(Banks::find('MOMO')['bin'])->toBe('971025')
        ->and(count(Banks::all()))->toBeGreaterThan(50);
});

it('lets members add a bank account by its VietQR code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('dashboard.bank-accounts.store'), [
            'bank_code' => 'ICB',
            'account_number' => '1234567890',
            'account_name' => 'Nguyễn Văn A',
        ])
        ->assertSessionHas('success');

    expect($user->bankAccounts()->sole())
        ->bank_code->toBe('ICB')
        ->bank_name->toBe('VietinBank')
        ->account_name->toBe('NGUYEN VAN A');

    $this->get(route('dashboard.bank-accounts.index'))->assertSee('https://cdn.vietqr.io/img/ICB.png', false);
});

it('rejects bank codes that are not in the list', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('dashboard.bank-accounts.store'), [
            'bank_code' => 'KHONGCO',
            'account_number' => '1234567890',
            'account_name' => 'Nguyen Van A',
        ])
        ->assertSessionHasErrors('bank_code');
});
