<?php

namespace App\Services\Affiliate;

use App\Exceptions\BusinessRuleException;

class AffiliateException extends BusinessRuleException
{
    public static function notConfigured(string $platform): self
    {
        return new self("Sàn {$platform} chưa được cấu hình API affiliate.");
    }
}
