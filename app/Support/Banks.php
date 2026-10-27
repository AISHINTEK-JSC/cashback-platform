<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Danh sách ngân hàng/ví nhận tiền lấy từ VietQR (api.vietqr.io/v2/banks), cache 1 ngày.
 * API lỗi thì dùng bản sao lưu FALLBACK (chụp từ API ngày 27/10/2026) để form thêm tài khoản không bao giờ trống.
 */
class Banks
{
    public const API_URL = 'https://api.vietqr.io/v2/banks';

    private const CACHE_KEY = 'banks.vietqr';

    /**
     * @var array<string, array{short_name: string, name: string, bin: string, logo: string}>
     */
    private const FALLBACK = [
        'ICB' => ['short_name' => 'VietinBank', 'name' => 'Ngân hàng TMCP Công thương Việt Nam', 'bin' => '970415', 'logo' => 'https://cdn.vietqr.io/img/ICB.png'],
        'VCB' => ['short_name' => 'Vietcombank', 'name' => 'Ngân hàng TMCP Ngoại Thương Việt Nam', 'bin' => '970436', 'logo' => 'https://cdn.vietqr.io/img/VCB.png'],
        'BIDV' => ['short_name' => 'BIDV', 'name' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam', 'bin' => '970418', 'logo' => 'https://cdn.vietqr.io/img/BIDV.png'],
        'VBA' => ['short_name' => 'Agribank', 'name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam', 'bin' => '970405', 'logo' => 'https://cdn.vietqr.io/img/VBA.png'],
        'OCB' => ['short_name' => 'OCB', 'name' => 'Ngân hàng TMCP Phương Đông', 'bin' => '970448', 'logo' => 'https://cdn.vietqr.io/img/OCB.png'],
        'MB' => ['short_name' => 'MBBank', 'name' => 'Ngân hàng TMCP Quân đội', 'bin' => '970422', 'logo' => 'https://cdn.vietqr.io/img/MB.png'],
        'TCB' => ['short_name' => 'Techcombank', 'name' => 'Ngân hàng TMCP Kỹ thương Việt Nam', 'bin' => '970407', 'logo' => 'https://cdn.vietqr.io/img/TCB.png'],
        'ACB' => ['short_name' => 'ACB', 'name' => 'Ngân hàng TMCP Á Châu', 'bin' => '970416', 'logo' => 'https://cdn.vietqr.io/img/ACB.png'],
        'VPB' => ['short_name' => 'VPBank', 'name' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng', 'bin' => '970432', 'logo' => 'https://cdn.vietqr.io/img/VPB.png'],
        'TPB' => ['short_name' => 'TPBank', 'name' => 'Ngân hàng TMCP Tiên Phong', 'bin' => '970423', 'logo' => 'https://cdn.vietqr.io/img/TPB.png'],
        'STB' => ['short_name' => 'Sacombank', 'name' => 'Ngân hàng TMCP Sài Gòn Tài Lộc', 'bin' => '970403', 'logo' => 'https://cdn.vietqr.io/img/SACOMBANK.png'],
        'HDB' => ['short_name' => 'HDBank', 'name' => 'Ngân hàng TMCP Phát triển Thành phố Hồ Chí Minh', 'bin' => '970437', 'logo' => 'https://cdn.vietqr.io/img/HDB.png'],
        'VCCB' => ['short_name' => 'VietCapitalBank', 'name' => 'Ngân hàng TMCP Bản Việt', 'bin' => '970454', 'logo' => 'https://cdn.vietqr.io/img/VCCB.png'],
        'SCB' => ['short_name' => 'SCB', 'name' => 'Ngân hàng TMCP Sài Gòn', 'bin' => '970429', 'logo' => 'https://cdn.vietqr.io/img/SCB.png'],
        'VIB' => ['short_name' => 'VIB', 'name' => 'Ngân hàng TMCP Quốc tế Việt Nam', 'bin' => '970441', 'logo' => 'https://cdn.vietqr.io/img/VIB.png'],
        'SHB' => ['short_name' => 'SHB', 'name' => 'Ngân hàng TMCP Sài Gòn - Hà Nội', 'bin' => '970443', 'logo' => 'https://cdn.vietqr.io/img/SHB.png'],
        'EIB' => ['short_name' => 'Eximbank', 'name' => 'Ngân hàng TMCP Xuất Nhập khẩu Việt Nam', 'bin' => '970431', 'logo' => 'https://cdn.vietqr.io/img/EIB.png'],
        'MSB' => ['short_name' => 'MSB', 'name' => 'Ngân hàng TMCP Hàng Hải', 'bin' => '970426', 'logo' => 'https://cdn.vietqr.io/img/MSB.png'],
        'CAKE' => ['short_name' => 'CAKE', 'name' => 'TMCP Việt Nam Thịnh Vượng - Ngân hàng số CAKE by VPBank', 'bin' => '546034', 'logo' => 'https://cdn.vietqr.io/img/CAKE.png'],
        'UBANK' => ['short_name' => 'Ubank', 'name' => 'TMCP Việt Nam Thịnh Vượng - Ngân hàng số Ubank by VPBank', 'bin' => '546035', 'logo' => 'https://cdn.vietqr.io/img/Ubank.png'],
        'TIMO' => ['short_name' => 'Timo', 'name' => 'Ngân hàng số Timo by Ban Viet Bank (Timo by Ban Viet Bank)', 'bin' => '963388', 'logo' => 'https://cdn.vietqr.io/img/TIMO.png'],
        'VTLMONEY' => ['short_name' => 'ViettelMoney', 'name' => 'Tổng Công ty Dịch vụ số Viettel - Chi nhánh tập đoàn công nghiệp viễn thông Quân Đội', 'bin' => '971005', 'logo' => 'https://cdn.vietqr.io/img/VTLMONEY.png'],
        'VNPTMONEY' => ['short_name' => 'VNPTMoney', 'name' => 'Trung tâm dịch vụ tài chính số VNPT- Chi nhánh Tổng công ty truyền thông (VNPT Fintech)', 'bin' => '971011', 'logo' => 'https://cdn.vietqr.io/img/VNPTMONEY.png'],
        'SGICB' => ['short_name' => 'SaigonBank', 'name' => 'Ngân hàng TMCP Sài Gòn Công Thương', 'bin' => '970400', 'logo' => 'https://cdn.vietqr.io/img/SGICB.png'],
        'BAB' => ['short_name' => 'BacABank', 'name' => 'Ngân hàng TMCP Bắc Á', 'bin' => '970409', 'logo' => 'https://cdn.vietqr.io/img/BAB.png'],
        'PVDB' => ['short_name' => 'PVcomBank Pay', 'name' => 'Ngân hàng số PVcomBank', 'bin' => '971133', 'logo' => 'https://cdn.vietqr.io/img/PVCB%20Pay.png'],
        'MOMO' => ['short_name' => 'MoMo', 'name' => 'CTCP Dịch Vụ Di Động Trực Tuyến', 'bin' => '971025', 'logo' => 'https://cdn.vietqr.io/img/momo.png'],
        'PVCB' => ['short_name' => 'PVcomBank', 'name' => 'Ngân hàng TMCP Đại Chúng Việt Nam', 'bin' => '970412', 'logo' => 'https://cdn.vietqr.io/img/PVCB.png'],
        'MBV' => ['short_name' => 'MBV', 'name' => 'Ngân hàng TNHH MTV Việt Nam Hiện Đại', 'bin' => '970414', 'logo' => 'https://cdn.vietqr.io/img/MBV.png'],
        'NCB' => ['short_name' => 'NCB', 'name' => 'Ngân hàng TMCP Quốc Dân', 'bin' => '970419', 'logo' => 'https://cdn.vietqr.io/img/NCB.png'],
        'SHBVN' => ['short_name' => 'ShinhanBank', 'name' => 'Ngân hàng TNHH MTV Shinhan Việt Nam', 'bin' => '970424', 'logo' => 'https://cdn.vietqr.io/img/SHBVN.png'],
        'ABB' => ['short_name' => 'ABBANK', 'name' => 'Ngân hàng TMCP An Bình', 'bin' => '970425', 'logo' => 'https://cdn.vietqr.io/img/ABB.png'],
        'VAB' => ['short_name' => 'VietABank', 'name' => 'Ngân hàng TMCP Việt Á', 'bin' => '970427', 'logo' => 'https://cdn.vietqr.io/img/VAB.png'],
        'NAB' => ['short_name' => 'NamABank', 'name' => 'Ngân hàng TMCP Nam Á', 'bin' => '970428', 'logo' => 'https://cdn.vietqr.io/img/NAB.png'],
        'PGB' => ['short_name' => 'PGBank', 'name' => 'Ngân hàng TMCP Thịnh vượng và Phát triển', 'bin' => '970430', 'logo' => 'https://cdn.vietqr.io/img/PGB.png'],
        'VIETBANK' => ['short_name' => 'VietBank', 'name' => 'Ngân hàng TMCP Việt Nam Thương Tín', 'bin' => '970433', 'logo' => 'https://cdn.vietqr.io/img/VIETBANK.png'],
        'BVB' => ['short_name' => 'BaoVietBank', 'name' => 'Ngân hàng TMCP Bảo Việt', 'bin' => '970438', 'logo' => 'https://cdn.vietqr.io/img/BVB.png'],
        'SEAB' => ['short_name' => 'SeABank', 'name' => 'Ngân hàng TMCP Đông Nam Á', 'bin' => '970440', 'logo' => 'https://cdn.vietqr.io/img/SEAB.png'],
        'COOPBANK' => ['short_name' => 'COOPBANK', 'name' => 'Ngân hàng Hợp tác xã Việt Nam', 'bin' => '970446', 'logo' => 'https://cdn.vietqr.io/img/COOPBANK.png'],
        'LPB' => ['short_name' => 'LPBank', 'name' => 'Ngân hàng TMCP Lộc Phát Việt Nam', 'bin' => '970449', 'logo' => 'https://cdn.vietqr.io/img/LPBANK.png'],
        'KLB' => ['short_name' => 'KienLongBank', 'name' => 'Ngân hàng TMCP Kiên Long', 'bin' => '970452', 'logo' => 'https://cdn.vietqr.io/img/KLB.png'],
        'KBANK' => ['short_name' => 'KBank', 'name' => 'Ngân hàng Đại chúng TNHH Kasikornbank', 'bin' => '668888', 'logo' => 'https://cdn.vietqr.io/img/KBank.png'],
        'KEBHANAHCM' => ['short_name' => 'KEBHanaHCM', 'name' => 'Ngân hàng KEB Hana – Chi nhánh Thành phố Hồ Chí Minh', 'bin' => '970466', 'logo' => 'https://cdn.vietqr.io/img/KEBHANAHCM.png'],
        'KEBHANAHN' => ['short_name' => 'KEBHANAHN', 'name' => 'Ngân hàng KEB Hana – Chi nhánh Hà Nội', 'bin' => '970467', 'logo' => 'https://cdn.vietqr.io/img/KEBHANAHN.png'],
        'MAFC' => ['short_name' => 'MAFC', 'name' => 'Công ty Tài chính TNHH MTV Mirae Asset (Việt Nam) ', 'bin' => '977777', 'logo' => 'https://cdn.vietqr.io/img/MAFC.png'],
        'VBSP' => ['short_name' => 'VBSP', 'name' => 'Ngân hàng Chính sách Xã hội', 'bin' => '999888', 'logo' => 'https://cdn.vietqr.io/img/VBSP.png'],
        'BNP HN' => ['short_name' => 'BNP HN', 'name' => 'Ngân hàng BNP Paribas – Chi nhánh Hà Nội', 'bin' => '963668', 'logo' => 'https://cdn.vietqr.io/img/BNP.png'],
        'BIDC' => ['short_name' => 'BIDC', 'name' => 'Ngân hàng Đầu Tư Và Phát Triển Campuchia - Chi nhánh Hà Nội', 'bin' => '555666', 'logo' => 'https://cdn.vietqr.io/img/BIDC.png'],
        'BNP HCM' => ['short_name' => 'BNP HCM', 'name' => 'Ngân hàng BNP Paribas – Chi nhánh Thành phố Hồ Chí Minh', 'bin' => '963666', 'logo' => 'https://cdn.vietqr.io/img/BNP.png'],
        'ZALOPAY' => ['short_name' => 'ZaloPay', 'name' => 'Ví điện tử ZaloPay', 'bin' => '971101', 'logo' => 'https://cdn.vietqr.io/img/ZALOPAY.png'],
        'BOCHK' => ['short_name' => 'BOCHK', 'name' => 'Ngân hàng Bank of China (Hong Kong) Limited – Chi nhánh Hồ Chí Minh', 'bin' => '963688', 'logo' => 'https://cdn.vietqr.io/img/BOCHK.png'],
        'BSP' => ['short_name' => 'BSP', 'name' => 'Ngân hàng SinoPac - Chi nhánh Thành phố Hồ Chí Minh', 'bin' => '970465', 'logo' => 'https://cdn.vietqr.io/img/BSP.png'],
        'CUBHCM' => ['short_name' => 'CUBHCM', 'name' => 'Ngân hàng Cathay United Bank – Chi nhánh Hồ Chí Minh', 'bin' => '168999', 'logo' => 'https://cdn.vietqr.io/img/CUB.png'],
        'ESUN' => ['short_name' => 'ESUN', 'name' => 'Ngân hàng Thương mại TNHH E.SUN – Chi nhánh Đồng Nai (ESUNBANK)', 'bin' => '963686', 'logo' => 'https://cdn.vietqr.io/img/ESUN.png'],
        'ICBC' => ['short_name' => 'ICBC', 'name' => 'Ngân hàng Công thương Trung Quốc – Chi nhánh thành phố Hà Nội', 'bin' => '963669', 'logo' => 'https://cdn.vietqr.io/img/ICBC.png'],
        'MVAS' => ['short_name' => 'MVAS', 'name' => 'Trung tâm Dịch vụ số Mobifone – Chi nhánh Tổng Công ty viễn thông Mobifone', 'bin' => '971032', 'logo' => 'https://cdn.vietqr.io/img/MVAS.png'],
        'TNEX' => ['short_name' => 'TNEX', 'name' => 'TNEX by MSB', 'bin' => '963326', 'logo' => 'https://cdn.vietqr.io/img/TNEX.png'],
        'HSBC' => ['short_name' => 'HSBC', 'name' => 'Ngân hàng TNHH MTV HSBC (Việt Nam)', 'bin' => '458761', 'logo' => 'https://cdn.vietqr.io/img/HSBC.png'],
        'HLBVN' => ['short_name' => 'HongLeong', 'name' => 'Ngân hàng TNHH MTV Hong Leong Việt Nam', 'bin' => '970442', 'logo' => 'https://cdn.vietqr.io/img/HLBVN.png'],
        'IBK - HN' => ['short_name' => 'IBKHN', 'name' => 'Ngân hàng Công nghiệp Hàn Quốc - Chi nhánh Hà Nội', 'bin' => '970455', 'logo' => 'https://cdn.vietqr.io/img/IBK.png'],
        'IBK - HCM' => ['short_name' => 'IBKHCM', 'name' => 'Ngân hàng Công nghiệp Hàn Quốc - Chi nhánh TP. Hồ Chí Minh', 'bin' => '970456', 'logo' => 'https://cdn.vietqr.io/img/IBK.png'],
        'IVB' => ['short_name' => 'IndovinaBank', 'name' => 'Ngân hàng TNHH Indovina', 'bin' => '970434', 'logo' => 'https://cdn.vietqr.io/img/IVB.png'],
        'VIKKI' => ['short_name' => 'Vikki', 'name' => 'Ngân hàng TNHH Một thành viên số Vikki', 'bin' => '970406', 'logo' => 'https://cdn.vietqr.io/img/DOB.png'],
        'NHB HN' => ['short_name' => 'Nonghyup', 'name' => 'Ngân hàng Nonghyup - Chi nhánh Hà Nội', 'bin' => '801011', 'logo' => 'https://cdn.vietqr.io/img/NHB%20HN.png'],
        'DBS' => ['short_name' => 'DBSBank', 'name' => 'DBS Bank Ltd - Chi nhánh Thành phố Hồ Chí Minh', 'bin' => '796500', 'logo' => 'https://cdn.vietqr.io/img/DBS.png'],
        'PBVN' => ['short_name' => 'PublicBank', 'name' => 'Ngân hàng TNHH MTV Public Việt Nam', 'bin' => '970439', 'logo' => 'https://cdn.vietqr.io/img/PBVN.png'],
        'SCVN' => ['short_name' => 'StandardChartered', 'name' => 'Ngân hàng TNHH MTV Standard Chartered Bank Việt Nam', 'bin' => '970410', 'logo' => 'https://cdn.vietqr.io/img/SCVN.png'],
        'CITIBANK' => ['short_name' => 'Citibank', 'name' => 'Ngân hàng Citibank, N.A. - Chi nhánh Hà Nội', 'bin' => '533948', 'logo' => 'https://cdn.vietqr.io/img/CITIBANK.png'],
        'CIMB' => ['short_name' => 'CIMB', 'name' => 'Ngân hàng TNHH MTV CIMB Việt Nam', 'bin' => '422589', 'logo' => 'https://cdn.vietqr.io/img/CIMB.png'],
        'UOB' => ['short_name' => 'UnitedOverseas', 'name' => 'Ngân hàng United Overseas - Chi nhánh TP. Hồ Chí Minh', 'bin' => '970458', 'logo' => 'https://cdn.vietqr.io/img/UOB.png'],
        'CBB' => ['short_name' => 'CBBank', 'name' => 'Ngân hàng Thương mại trách nhiệm hữu hạn một thành viên Ngoại thương Công nghệ số (Vietcombank Neo Limited)', 'bin' => '970444', 'logo' => 'https://cdn.vietqr.io/img/VCBNeo.png'],
        'GPB' => ['short_name' => 'GPBank', 'name' => 'Ngân hàng Thương mại TNHH MTV Dầu Khí Toàn Cầu', 'bin' => '970408', 'logo' => 'https://cdn.vietqr.io/img/GPB.png'],
        'VRB' => ['short_name' => 'VRB', 'name' => 'Ngân hàng Liên doanh Việt - Nga', 'bin' => '970421', 'logo' => 'https://cdn.vietqr.io/img/VRB.png'],
        'WVN' => ['short_name' => 'Woori', 'name' => 'Ngân hàng TNHH MTV Woori Việt Nam', 'bin' => '970457', 'logo' => 'https://cdn.vietqr.io/img/WVN.png'],
        'KBHN' => ['short_name' => 'KookminHN', 'name' => 'Ngân hàng Kookmin - Chi nhánh Hà Nội', 'bin' => '970462', 'logo' => 'https://cdn.vietqr.io/img/KB.png'],
        'KBHCM' => ['short_name' => 'KookminHCM', 'name' => 'Ngân hàng Kookmin - Chi nhánh Thành phố Hồ Chí Minh', 'bin' => '970463', 'logo' => 'https://cdn.vietqr.io/img/KB.png'],
    ];

    /**
     * Mã ngân hàng => tên ngắn, theo thứ tự phổ biến của VietQR.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return array_map(fn (array $bank) => $bank['short_name'], static::list());
    }

    /**
     * @return array{short_name: string, name: string, bin: string, logo: string}|null
     */
    public static function find(string $code): ?array
    {
        return static::list()[strtoupper($code)] ?? null;
    }

    /**
     * @return array<string, array{short_name: string, name: string, bin: string, logo: string}>
     */
    public static function list(): array
    {
        $banks = Cache::get(self::CACHE_KEY);

        if ($banks === null) {
            $banks = static::fetch();
            // API lỗi thì chỉ nhớ bản sao lưu 10 phút để sớm thử lại
            Cache::put(self::CACHE_KEY, $banks ?? self::FALLBACK, $banks !== null ? now()->addDay() : now()->addMinutes(10));
        }

        return $banks ?? self::FALLBACK;
    }

    /**
     * @return array<string, array{short_name: string, name: string, bin: string, logo: string}>|null
     */
    private static function fetch(): ?array
    {
        try {
            $response = Http::timeout(5)->acceptJson()->get(self::API_URL);
        } catch (Throwable) {
            return null;
        }

        $banks = collect($response->successful() ? $response->json('data', []) : [])
            ->filter(fn ($bank) => is_array($bank) && filled($bank['code'] ?? null) && filled($bank['shortName'] ?? null))
            ->mapWithKeys(fn (array $bank) => [strtoupper($bank['code']) => [
                'short_name' => (string) $bank['shortName'],
                'name' => (string) ($bank['name'] ?? $bank['shortName']),
                'bin' => (string) ($bank['bin'] ?? ''),
                'logo' => (string) ($bank['logo'] ?? ''),
            ]])
            ->all();

        return $banks !== [] ? $banks : null;
    }
}
