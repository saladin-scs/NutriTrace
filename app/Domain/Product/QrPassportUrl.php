<?php

namespace App\Domain\Product;

final class QrPassportUrl
{
    public function forCode(string $code): string
    {
        return url('/trace/'.$code);
    }

    public function qrImageUrl(string $code, int $size = 240): string
    {
        $data = urlencode($this->forCode($code));

        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$data}";
    }
}
