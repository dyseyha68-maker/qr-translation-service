<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReferralCode;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function __construct(
        protected TelegramReferralLinkService $linkService
    ) {}

    /**
     * Generate an SVG representation of the QR code for a given string.
     */
    public function generateSvg(string $data, int $size = 300, int $margin = 1): string
    {
        return (string) QrCode::size($size)
            ->margin($margin)
            ->errorCorrection('M')
            ->generate($data);
    }

    /**
     * Generate a data URI string containing the SVG QR code.
     */
    public function generateDataUri(string $data, int $size = 300, int $margin = 1): string
    {
        $svg = $this->generateSvg($data, $size, $margin);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate SVG QR code specifically for a ReferralCode model.
     */
    public function generateForReferralCode(ReferralCode $referralCode, int $size = 300, int $margin = 1): string
    {
        $url = $this->linkService->buildForReferralCode($referralCode);

        return $this->generateSvg($url, $size, $margin);
    }

    /**
     * Generate a download response for the referral QR code.
     */
    public function downloadResponse(ReferralCode $referralCode, int $size = 500): Response
    {
        $url = $this->linkService->buildForReferralCode($referralCode);
        $svg = $this->generateSvg($url, $size, 2);
        $filename = "qr-{$referralCode->code}.svg";

        return new Response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
