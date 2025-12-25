<?php

use Illuminate\Support\Facades\Route;
use Azeemade\BulkUpload\Controllers\BulkUploadController;

Route::prefix('api/bulk-upload')->group(function () {
    Route::get('template', [BulkUploadController::class, 'template']);
    Route::post('import', [BulkUploadController::class, 'import']);
    Route::get('{batchId}', [BulkUploadController::class, 'status']);
    Route::get('{batchId}/errors', [BulkUploadController::class, 'downloadErrors'])->name('bulk-upload.errors');
});
