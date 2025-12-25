<?php

namespace Azeemade\BulkUpload\Contracts;

interface BulkUploadable
{
    /**
     * Get the validation rules for a single row.
     *
     * @param array $row
     * @return array
     */
    public function getUploadValidationRules(array $row): array;

    /**
     * Process a single row from the upload.
     *
     * @param array $row
     * @return void
     */
    public function processUploadRow(array $row): void;

    /**
     * Get the columns that should be present in the template.
     *
     * @return array
     */
    public function getTemplateColumns(): array;

    /**
     * Hook to run when the bulk upload is completed.
     *
     * @param \Azeemade\BulkUpload\Models\BulkUpload $bulkUpload
     * @return void
     */
    public function onUploadComplete($bulkUpload): void;

    /**
     * Get sample data for the template.
     *
     * @return array
     */
    public function getTemplateSample(): array;

    /**
     * Get options/descriptions for the template columns.
     *
     * @return array
     */
    public function getTemplateOptions(): array;
}
