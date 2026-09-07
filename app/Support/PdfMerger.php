<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

class PdfMerger
{
    public function merge(array $paths): string
    {
        if ($paths === []) {
            throw new RuntimeException('No report PDFs are available to merge.');
        }

        $arguments = [(string) config('pdf.qpdf_binary'), '--empty', '--pages'];
        foreach ($paths as $path) {
            $resolvedPath = realpath($path);
            if ($resolvedPath === false || ! is_readable($resolvedPath)) {
                throw new RuntimeException('A report attachment could not be read.');
            }
            $arguments[] = $resolvedPath;
        }
        array_push($arguments, '--', '-');

        $process = new Process($arguments);
        $process->setTimeout(120);
        $process->run();

        // QPDF returns 3 when it produced output with recoverable warnings.
        if (! in_array($process->getExitCode(), [0, 3], true)) {
            throw new RuntimeException('Unable to merge the report PDFs. Verify QPDF is installed and QPDF_BINARY is configured, and that the blood result PDFs are readable and not password protected.');
        }

        $pdf = $process->getOutput();
        if (! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('The PDF merger did not produce a valid report.');
        }

        return $pdf;
    }
}
