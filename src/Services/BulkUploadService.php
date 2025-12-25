<?php

namespace Azeemade\BulkUpload\Services;

use Azeemade\BulkUpload\Contracts\BulkUploadable;
use Azeemade\BulkUpload\Jobs\ProcessBulkUploadJob;
use Azeemade\BulkUpload\Models\BulkUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;



class BulkUploadService
{
    /**
     * Resolve model class from name or alias.
     */
    public function resolveModel(string $identifier): string
    {
        $map = config('bulk-upload.model_map', []);
        if (isset($map[$identifier])) {
            return $map[$identifier];
        }

        // If it's a direct class name
        if (class_exists($identifier)) {
            return $identifier;
        }

        throw new \Exception("Model not found for identifier: {$identifier}");
    }

    /**
     * Handle the upload process.
     */
    public function handle(string $modelIdentifier, UploadedFile $file)
    {
        $modelClass = $this->resolveModel($modelIdentifier);

        // 1. Validate Model
        if (!in_array(BulkUploadable::class, class_implements($modelClass))) {
            throw new \Exception("Model {$modelClass} must implement BulkUploadable interface.");
        }

        // 2. Store File
        $disk = config('bulk-upload.disk', 'local');
        $path = $file->store('bulk-uploads/originals', $disk);

        // 3. Count Rows (using HeadingRowImport to be lightweight first, then maybe count)
        // Note: Counting rows in excel can be heavy.
        // For now, we will just create the record and let the job/sync process count it or estimation.
        // Actually, to decide sync vs queue, we really need the count.
        // Let's use a quick import to count.
        $rows = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        }, $file);
        // Note: reading whole file to array might be memory intensive for HUGE files.
        // Optimally we'd use a chunk reading or something lighter.
        // But for "bulk upload" usually < 10k rows, this is fine.

        $flattened = $rows[0] ?? [];
        $rowCount = count($flattened);
        // Subtract header if needed, usually ToArray includes header.

        // 4. Create Record
        $batchId = (string) Str::uuid();

        $user = auth()->user();
        $tenantId = null;

        // Try to resolve tenant from generic methods or properties if they exist
        if ($user && config('bulk-upload.multitenancy.enabled', false)) {
            if (method_exists($user, 'getTenantId')) {
                $tenantId = $user->getTenantId();
            } elseif (property_exists($user, 'tenant_id')) {
                $tenantId = $user->tenant_id;
            }
        }

        $bulkUpload = BulkUpload::create([
            'batch_id' => $batchId,
            'model_class' => $modelClass,
            'file_path' => $path,
            'total_rows' => $rowCount, // approximate
            'status' => 'pending',
            'user_id' => $user ? $user->getAuthIdentifier() : null,
            'user_type' => $user ? get_class($user) : null,
            'tenant_id' => $tenantId,
        ]);

        // 5. Decision: Sync or Queue
        $threshold = config('bulk-upload.queue_threshold', 500);

        if ($rowCount > $threshold) {
            ProcessBulkUploadJob::dispatch($bulkUpload);
            $bulkUpload->update(['status' => 'processing']);
            return $bulkUpload;
        }

        // Process Synchronously
        $this->process($bulkUpload);

        return $bulkUpload->fresh();
    }

    public function process(BulkUpload $bulkUpload)
    {
        $bulkUpload->update(['status' => 'processing']);

        // Processing logic actually needs to read the file again and iterate.
        // We will move the actual processing logic to a dedicated Import class or method
        // so it can be reused by the Job.

        try {
            // Logic to process rows
            $importer = new \Azeemade\BulkUpload\Imports\BulkImporter($bulkUpload);
            Excel::import($importer, $bulkUpload->file_path, config('bulk-upload.disk'));

            // Check if we need to generate error file is handled inside Importer or after.

        } catch (\Exception $e) {
            $bulkUpload->update(['status' => 'failed', 'meta' => ['error' => $e->getMessage()]]);
        }
    }

    public function generateTemplate(string $modelIdentifier)
    {
        $modelClass = $this->resolveModel($modelIdentifier);
        $model = new $modelClass;

        if (!method_exists($model, 'getTemplateColumns')) {
            throw new \Exception("Model does not implement BulkUploadable");
        }

        $columns = $model->getTemplateColumns();
        $sample = $model->getTemplateSample();
        $options = $model->getTemplateOptions();

        return new class($columns, $sample, $options) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            protected $columns;
            protected $sample;
            protected $options;

            public function __construct(array $columns, array $sample, array $options)
            {
                $this->columns = $columns;
                $this->sample = $sample;
                $this->options = $options;
            }

            public function array(): array
            {
                $data = [];
                if (!empty($this->options)) {
                    $data[] = $this->options;
                }
                if (!empty($this->sample)) {
                    $data[] = $this->sample;
                }
                return $data;
            }

            public function headings(): array
            {
                return $this->columns;
            }
        };
    }

    public function getDownloadableErrorFile(string $batchId)
    {
        $bulkUpload = BulkUpload::where('batch_id', $batchId)->firstOrFail();

        if (!$bulkUpload->error_file_path || !\Illuminate\Support\Facades\Storage::disk(config('bulk-upload.disk'))->exists($bulkUpload->error_file_path)) {
            throw new \Exception("Error file not found");
        }

        return \Illuminate\Support\Facades\Storage::disk(config('bulk-upload.disk'))->path($bulkUpload->error_file_path);
    }

    public function getBatchStatus(string $batchId)
    {
        $bulkUpload = BulkUpload::where('batch_id', $batchId)->firstOrFail();

        return [
            'data' => $bulkUpload,
            'download_error_sheet_url' => $bulkUpload->error_file_path
                ? route('bulk-upload.errors', ['batchId' => $bulkUpload->batch_id])
                : null
        ];
    }
}
