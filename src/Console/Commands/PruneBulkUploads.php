<?php

namespace Azeemade\BulkUpload\Console\Commands;

use Azeemade\BulkUpload\Models\BulkUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneBulkUploads extends Command
{
    protected $signature = 'bulk-upload:cleanup';
    protected $description = 'Cleanup old bulk upload files and records';

    public function handle()
    {
        $days = config('bulk-upload.prune_after_days', 1);
        $date = now()->subDays($days);

        $uploads = BulkUpload::where('created_at', '<', $date)->get();

        foreach ($uploads as $upload) {
            // Delete files
            if ($upload->file_path) {
                Storage::disk(config('bulk-upload.disk'))->delete($upload->file_path);
            }
            if ($upload->error_file_path) {
                Storage::disk(config('bulk-upload.disk'))->delete($upload->error_file_path);
            }

            // Delete record
            $upload->delete();
        }

        $this->info('Cleaned up ' . $uploads->count() . ' bulk uploads.');
    }
}
