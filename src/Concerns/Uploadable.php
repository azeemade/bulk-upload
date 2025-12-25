<?php

namespace Azeemade\BulkUpload\Concerns;

use Azeemade\BulkUpload\Models\BulkUpload;

trait Uploadable
{
    /**
     * Hook to run when the bulk upload is completed.
     * Default implementation is empty.
     *
     * @param BulkUpload $bulkUpload
     * @return void
     */
    public function onUploadComplete($bulkUpload): void
    {
        // Optional hook
    }

    /**
     * Default to fillable attributes if not overridden.
     *
     * @return array
     */
    public function getTemplateColumns(): array
    {
        return $this->getFillable();
    }

    public function getTemplateSample(): array
    {
        return [];
    }

    public function getTemplateOptions(): array
    {
        return [];
    }
}
