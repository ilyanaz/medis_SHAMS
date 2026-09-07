<?php

return [
    'qpdf_binary' => env('QPDF_BINARY', PHP_OS_FAMILY === 'Windows'
        ? storage_path('app/tools/qpdf/qpdf-12.4.1-msvc64/bin/qpdf.exe')
        : 'qpdf'),
];
