<?php

namespace Azeemade\BulkUpload\Controllers;

use Azeemade\BulkUpload\Models\BulkUpload;
use Azeemade\BulkUpload\Services\BulkUploadService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;

class BulkUploadController extends Controller
{
    public function template(Request $request, BulkUploadService $service)
    {
        $request->validate([
            'model' => 'required|string',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        try {
            $format = $request->get('format', 'csv');
            $export = $service->generateTemplate($request->get('model'));
            return Excel::download($export, 'template.' . $format);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function import(Request $request, BulkUploadService $service)
    {
        $maxSize = config('bulk-upload.max_upload_size', 10240);

        $request->validate([
            'model' => 'required|string',
            'file' => 'required|file|mimes:csv,xlsx,xls|max:' . $maxSize,
            'metadata' => 'nullable|array',
        ]);

        try {
            $bulkUpload = $service->handle($request->get('model'), $request->file('file'), $request->get('metadata', []));
            return response()->json([
                'message' => 'Upload processed successfully',
                'data' => $bulkUpload
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function status($batchId, BulkUploadService $service)
    {
        try {
            return response()->json($service->getBatchStatus($batchId));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Batch not found'], 404);
        }
    }

    public function downloadErrors($batchId, BulkUploadService $service)
    {
        try {
            $path = $service->getDownloadableErrorFile($batchId);
            return response()->download($path);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
