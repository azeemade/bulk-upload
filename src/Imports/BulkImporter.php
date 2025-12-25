<?php

namespace Azeemade\BulkUpload\Imports;

use Azeemade\BulkUpload\Models\BulkUpload;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class BulkImporter implements ToCollection, WithHeadingRow
{
    protected $bulkUpload;
    protected $model;
    protected $errors = [];
    protected $processedCount = 0;
    protected $successCount = 0;
    protected $failCount = 0;

    public function __construct(BulkUpload $bulkUpload)
    {
        $this->bulkUpload = $bulkUpload;
        $class = $bulkUpload->model_class;
        $this->model = new $class;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->processedCount++;
            $rowData = $row->toArray();

            // 1. Validate
            $rules = $this->model->getUploadValidationRules($rowData);
            $validator = Validator::make($rowData, $rules);

            if ($validator->fails()) {
                $this->failCount++;
                $this->errors[] = array_merge($rowData, ['_errors' => implode('; ', $validator->errors()->all())]);
                continue;
            }

            // 2. Process
            try {
                $this->model->processUploadRow($rowData);
                $this->successCount++;
            } catch (\Exception $e) {
                $this->failCount++;
                $this->errors[] = array_merge($rowData, ['_errors' => $e->getMessage()]);
            }
        }

        $this->finalize();
    }

    protected function finalize()
    {
        // Update Stats
        $this->bulkUpload->update([
            'processed_rows' => $this->processedCount,
            'successful_rows' => $this->successCount,
            'failed_rows' => $this->failCount,
        ]);

        // Generate Error Sheet
        if (!empty($this->errors)) {
            $format = config('bulk-upload.error_format', 'csv');
            $errorFileName = 'bulk-uploads/errors/error_' . $this->bulkUpload->batch_id . '.' . $format;

            // We can use Maatwebsite to export the array to CSV/Excel
            $export = new class ($this->errors) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                protected $data;
                public function __construct(array $data)
                {
                    $this->data = $data;
                }
                public function array(): array
                {
                    return $this->data;
                }
                public function headings(): array
                {
                    return array_keys($this->data[0] ?? []);
                }
            };

            Excel::store($export, $errorFileName, config('bulk-upload.disk'));

            $this->bulkUpload->update(['error_file_path' => $errorFileName]);
        }

        // Final Status
        if ($this->failCount > 0) {
            $status = $this->successCount > 0 ? 'partially_failed' : 'failed';
        } else {
            $status = 'completed';
            // Delete original file on success
            if ($this->bulkUpload->file_path) {
                \Illuminate\Support\Facades\Storage::disk(config('bulk-upload.disk'))->delete($this->bulkUpload->file_path);
            }
        }

        $this->bulkUpload->update(['status' => $status]);

        // Hook
        $this->model->onUploadComplete($this->bulkUpload);

        $email = config('bulk-upload.notify_email');
        if ($email) {
            \Illuminate\Support\Facades\Notification::route('mail', $email)
                ->notify(new \Azeemade\BulkUpload\Notifications\BulkUploadProcessed($this->bulkUpload));
        }
    }
}
