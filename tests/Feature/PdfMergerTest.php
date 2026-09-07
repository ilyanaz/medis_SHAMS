<?php

namespace Tests\Feature;

use App\Support\PdfMerger;
use FPDF;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfMergerTest extends TestCase
{
    public function test_merges_compressed_blood_result_after_report(): void
    {
        $binary = (string) config('pdf.qpdf_binary');
        $probe = new Process([$binary, '--version']);
        $probe->run();
        if (! $probe->isSuccessful()) {
            $this->markTestSkipped('Install QPDF to run the PDF merge integration test.');
        }

        $paths = [];
        try {
            for ($index = 0; $index < 4; $index++) {
                $paths[] = tempnam(sys_get_temp_dir(), 'medis-qpdf-test-');
            }
            $report = new FPDF();
            $report->AddPage('P', 'A4');
            $report->SetFont('Helvetica', '', 12);
            $report->Cell(40, 10, 'Examination report');
            $report->Output('F', $paths[0]);

            $blood = new FPDF();
            $blood->AddPage('L', 'A4');
            $blood->SetFont('Helvetica', '', 12);
            $blood->Cell(40, 10, 'Blood result');
            $blood->AddPage('P', 'A4');
            $blood->Output('F', $paths[1]);
            (new Process([$binary, '--object-streams=generate', $paths[1], $paths[2]]))->mustRun();
            $this->assertStringContainsString('/ObjStm', file_get_contents($paths[2]));

            file_put_contents($paths[3], app(PdfMerger::class)->merge([$paths[0], $paths[2]]));
            $check = new Process([$binary, '--check', $paths[3]]);
            $check->mustRun();
            $pages = new Process([$binary, '--show-npages', $paths[3]]);
            $pages->mustRun();
            $this->assertSame('3', trim($pages->getOutput()));

            // Confirm the source pages remain in order, with their original sizes.
            $normalized = $paths[1];
            (new Process([$binary, '--object-streams=disable', $paths[3], $normalized]))->mustRun();
            $reader = new \setasign\Fpdi\Fpdi();
            $reader->setSourceFile($normalized);
            foreach ([1 => false, 2 => true, 3 => false] as $number => $landscape) {
                $size = $reader->getTemplateSize($reader->importPage($number));
                $this->assertSame($landscape, $size['width'] > $size['height']);
            }
        } finally {
            foreach ($paths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_missing_attachment_is_not_silently_omitted(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('attachment could not be read');
        app(PdfMerger::class)->merge([__DIR__ . '/missing-blood-result.pdf']);
    }
}
