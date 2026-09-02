<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Storage::disk('public')->allFiles() as $path) {
            if ($path === '.gitignore') {
                continue;
            }

            if (! Storage::disk('private')->exists($path)) {
                Storage::disk('private')->put($path, Storage::disk('public')->get($path));
            }

            Storage::disk('public')->delete($path);
        }
    }

    public function down(): void
    {
        // Moving confidential files back to public storage would be unsafe.
    }
};
