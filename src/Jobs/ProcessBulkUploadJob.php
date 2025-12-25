<?php

namespace Azeemade\BulkUpload\Jobs;

use Azeemade\BulkUpload\Models\BulkUpload;
use Azeemade\BulkUpload\Services\BulkUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBulkUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bulkUpload;

    public function __construct(BulkUpload $bulkUpload)
    {
        $this->bulkUpload = $bulkUpload;
    }

    public function handle(BulkUploadService $service)
    {
        $service->process($this->bulkUpload);
    }
}
