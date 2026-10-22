<?php

namespace App\Support;

/**
 * Danh sách ngân hàng/ví nhận tiền phổ biến tại Việt Nam.
 */
class Banks
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'VCB' => 'Vietcombank',
            'TCB' => 'Techcombank',
            'MB' => 'MB Bank',
            'ACB' => 'ACB',
            'BIDV' => 'BIDV',
            'VTB' => 'VietinBank',
            'VPB' => 'VPBank',
            'TPB' => 'TPBank',
            'AGR' => 'Agribank',
            'STB' => 'Sacombank',
            'VIB' => 'VIB',
            'SHB' => 'SHB',
            'HDB' => 'HDBank',
            'OCB' => 'OCB',
            'MSB' => 'MSB',
            'SCB' => 'SCB',
            'EIB' => 'Eximbank',
            'LPB' => 'LPBank',
            'SEAB' => 'SeABank',
            'CAKE' => 'Cake by VPBank',
            'TIMO' => 'Timo',
            'MOMO' => 'Ví MoMo',
            'ZALOPAY' => 'ZaloPay',
        ];
    }
}
