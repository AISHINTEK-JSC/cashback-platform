<?php

namespace App\Support;

use App\Models\Withdrawal;

/**
 * Mã VietQR để admin quét bằng app ngân hàng khi chi trả rút tiền, dùng link nhanh img.vietqr.io (định dạng SVG).
 * Giới hạn của VietQR: số tài khoản tối đa 19 ký tự, nội dung tối đa 50 ký tự không dấu, 10 mã/10 giây mỗi IP.
 */
class VietQr
{
    private const QUICK_LINK = 'https://img.vietqr.io/image';

    private const TEMPLATE = 'compact2';

    /**
     * Link ảnh QR chứa sẵn ngân hàng, số tài khoản, số tiền thực chuyển và nội dung là mã yêu cầu;
     * null khi ngân hàng không có trong danh sách VietQR hoặc số tài khoản vượt giới hạn.
     */
    public static function forWithdrawal(Withdrawal $withdrawal): ?string
    {
        $bin = static::bankBin($withdrawal);
        $accountNumber = preg_replace('/\s+/', '', $withdrawal->account_number);

        if (! $bin || $accountNumber === '' || strlen($accountNumber) > 19) {
            return null;
        }

        return sprintf('%s/%s-%s-%s.svg?%s', self::QUICK_LINK, $bin, $accountNumber, self::TEMPLATE, http_build_query([
            'amount' => $withdrawal->net_amount,
            'addInfo' => $withdrawal->code,
            'accountName' => $withdrawal->account_name,
        ], encoding_type: PHP_QUERY_RFC3986));
    }

    /**
     * Yêu cầu rút chỉ lưu tên ngân hàng; lấy mã qua tài khoản nhận tiền, đã bị xoá thì dò theo tên.
     */
    private static function bankBin(Withdrawal $withdrawal): ?string
    {
        $bank = $withdrawal->bankAccount
            ? Banks::find($withdrawal->bankAccount->bank_code)
            : collect(Banks::list())->firstWhere('short_name', $withdrawal->bank_name);

        return filled($bank['bin'] ?? null) ? $bank['bin'] : null;
    }
}
