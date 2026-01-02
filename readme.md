# Laravel Bulk Upload Package

A powerful, fully customizable Laravel package for handling bulk uploads with ease. This package supports model-dependent validation, queue vs sync processing, multi-tenancy, and template generation.

## Features

- 🚀 **Sync & Queue Support**: Automatically switches between synchronous and asynchronous processing based on configurable thresholds.
- 🛡️ **Robust Validation**: Row-by-row validation using standard Laravel rules defined in your Model.
- 🏢 **Multi-tenancy Ready**: Built-in support for multi-tenancy (tenant ID tracking) and user tracking.
- 📄 **Automatic Templates**: Generate CSV templates dynamically based on your Model's fillable attributes.
- ⚠️ **Error Handling**: Generates a detailed error CSV for failed rows, allowing users to correct and re-upload.
- 🧹 **Auto-Cleanup**: Automatically cleans up old files and temporary records.
- 📧 **Notifications**: Configurable email notifications upon upload completion.

## Installation

You can install the package via composer:

```bash
composer require azeemade/bulk-upload
```

After installing, publish the configuration and migrations:

```bash
php artisan vendor:publish --provider="Azeemade\BulkUpload\BulkUploadServiceProvider"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

You can configure the package in `config/bulk-upload.php`. Key options include:

- `queue_threshold`: Number of rows to trigger background processing (Default: 500).
- `max_upload_size`: Maximum file size in KB (Default: 10MB).
- `notify_email`: Email address to receive upload completion notifications.
- `multitenancy.enabled`: Enable auto-capture of Tenant ID.
- `prune_after_days`: Days to keep failed upload files before cleanup.
- `error_format`: Format for error sheets (`csv` or `xlsx`, Default: `csv`).
- `model_map`: Map aliases to full class names (e.g., `'product' => \App\Models\Product::class`).

## Usage

### 1. Prepare Your Model

Implement the `BulkUploadable` interface and use the `Uploadable` trait on any Eloquent model you want to support import for.

```php
use Azeemade\BulkUpload\Contracts\BulkUploadable;
use Azeemade\BulkUpload\Concerns\Uploadable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model implements BulkUploadable
{
    use Uploadable;

    protected $fillable = ['sku', 'name', 'price', 'stock'];

    /**
     * Define validation rules for a single row.
     */
    public function getUploadValidationRules(array $row): array
    {
        return [
            'sku' => 'required|unique:products,sku',
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'integer|min:0',
        ];
    }

    /**
     * Optional: Provide sample data for the template.
     */
    public function getTemplateSample(): array
    {
        return [
            'sku' => 'PROD-001',
            'name' => 'Sample Product',
            'price' => 10.50,
            'stock' => 100,
        ];
    }

    /**
     * Optional: Provide description/options for the template.
     */
    public function getTemplateOptions(): array
    {
        return [
            'sku' => 'Unique product code',
            'name' => 'Product Name',
            'price' => 'Decimal value',
            'stock' => 'Integer only',
        ];
    }

    /**
     * Process a single valid row.
     */
    public function processUploadRow(array $row): void
    {
        $this->create([
            'sku' => $row['sku'],
            'name' => $row['name'],
            'price' => $row['price'],
            'stock' => $row['stock'] ?? 0,
        ]);
    }
}
```

### 2. API Endpoints

The package exposes standard API routes for handling uploads.

#### **Download Template**

Returns a CSV or Excel template based on the model's fillable fields.

```http
GET /api/bulk-upload/template?model=App\Models\Product&format=xlsx
```

(Supported formats: `csv`, `xlsx`. Default: `csv`)

#### **Upload File**

Upload a CSV or Excel file.

```http
POST /api/bulk-upload/import
Content-Type: multipart/form-data

model: App\Models\Product
model: App\Models\Product
file: (binary)
metadata[source]: api       # Optional metadata
metadata[priority]: high
```

#### **Using Metadata in Model**

If you send metadata with the upload request, implement the `setBulkUploadMetadata` method in your model to receive it.

```php
    /**
     * Receive metadata from the upload request.
     */
    public function setBulkUploadMetadata(array $metadata): void
    {
        if (isset($metadata['priority']) && $metadata['priority'] === 'high') {
            // Handle high priority logic
        }
    }
```

#### **Response:**

```json
{
  "message": "Upload processed successfully",
  "data": {
    "batch_id": "9a1b2c...",
    "status": "processing",
    "total_rows": 1200
  }
}
```

#### **Check Status**

Poll this endpoint to get progress.

```http
GET /api/bulk-upload/{batch_id}
```

**Response:**

```json
{
  "data": {
    "id": 1,
    "batch_id": "9a1b2c...",
    "status": "completed",
    "processed_rows": 1200,
    "successful_rows": 1195,
    "failed_rows": 5,
    "user_id": 1,
    "tenant_id": null
  },
  "download_error_sheet_url": "http://domain.com/api/bulk-upload/9a1b2c.../errors"
}
```

### 3. Cleanup Task

To ensure your storage doesn't fill up with old error sheets, add the cleanup command to your `app/Console/Kernel.php`:

```php
$schedule->command('bulk-upload:cleanup')->daily();
```

### 4. Advanced: Using Custom Traits

The `Uploadable` trait provided by this package is optional. It simply provides default implementations for the interface methods.

If you prefer to organize your logic in your own Traits (e.g., `WorkerTrait`), **do not use** the `Azeemade\BulkUpload\Concerns\Uploadable` trait. Instead, just implement the `BulkUploadable` interface and define the methods in your own trait.

```php
class Worker extends Model implements BulkUploadable
{
    use WorkerTrait; // Contains getUploadValidationRules, processUploadRow, etc.
}
```

### 5. Advanced: Custom Controller

You can use the `BulkUploadService` directly in your own controllers:

```php
public function import(Request $request, \Azeemade\BulkUpload\Services\BulkUploadService $service)
{
    // Use alias from config or full class name
    $batch = $service->handle('product', $request->file('file'));

    return response()->json($batch);
}
```

## Credits

- [Azeem](https://github.com/azeemade)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
