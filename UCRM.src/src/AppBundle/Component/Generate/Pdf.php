<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Generate;

use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;

class Pdf
{
    const PAGE_ORIENTATION_LANDSCAPE = 'landscape';
    const PAGE_ORIENTATION_PORTRAIT = 'portrait';

    const PAGE_SIZE_US_LETTER = 'letter';
    const PAGE_SIZE_US_HALF_LETTER = 'half-letter';
    const PAGE_SIZE_US_LEGAL = 'legal';
    const PAGE_SIZE_A4 = 'A4';
    const PAGE_SIZE_A5 = 'A5';
    const PAGE_SIZES = [
        self::PAGE_SIZE_US_LETTER => 'US letter',
        self::PAGE_SIZE_US_HALF_LETTER => 'US half letter',
        self::PAGE_SIZE_US_LEGAL => 'US legal',
        self::PAGE_SIZE_A4 => 'A4',
        self::PAGE_SIZE_A5 => 'A5',
    ];

    /**
     * @var string
     */
    private $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function generateFromHtml(
        string $html,
        string $pageSize = self::PAGE_SIZE_US_LETTER,
        string $orientation = self::PAGE_ORIENTATION_PORTRAIT
    ): string {
        $limit = ini_get('max_execution_time');
        set_time_limit(300);
        try {
            $pdf = $this->getDompdf($pageSize, $orientation, false);
            $pdf->loadHtml($html);
            $pdf->render();
            $output = $pdf->output();
        } catch (\Throwable $exception) {
            // If standard parser fails, try to render with experimental HTML5 parser (more tolerant to invalid HTML).

            // \Throwable is caught because of "Call to a member function get_cellmap() on null" error,
            // which is often fine in HTML 5 renderer.
            // @see https://github.com/dompdf/dompdf/issues/1416

            $pdf = $this->getDompdf($pageSize, $orientation, true);
            $pdf->loadHtml($html);
            $pdf->render();
            $output = $pdf->output();
        } finally {
            set_time_limit((int) $limit);
        }

        if (null === $output) {
            throw new PdfException('PDF generation failed.');
        }

        return $output;
    }

    private function getDompdf(string $pageSize, string $orientation, bool $useHtml5Parser): Dompdf
    {
        $options = new DompdfOptions(
            [
                'fontDir' => sprintf(
                    '%s/../web/assets/fonts/dompdf',
                    $this->rootDir
                ),
                'isJavascriptEnabled' => false,
                'isRemoteEnabled' => true,
                'fontHeightRatio' => 1.0,
                'isFontSubsettingEnabled' => true,
                'isHtml5ParserEnabled' => $useHtml5Parser,
            ]
        );

        $pdf = new Dompdf($options);
        $pdf->setPaper($pageSize, $orientation);

        return $pdf;
    }
}
