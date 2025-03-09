<?php

namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class QrCodeService
{
    public function generateQrCode(string $url): string
    {
        // Create QR code with all parameters in constructor
        $qrCode = new QrCode(
            $url,
            new Encoding('UTF-8'),
            ErrorCorrectionLevel::High,
            200,  // size
            10    // margin
        );

        // Create writer and generate data URI
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }
}
