<?php

namespace Azeemade\BulkUpload\Tests\Feature;

use Azeemade\BulkUpload\Contracts\BulkUploadable;
use Azeemade\BulkUpload\Models\BulkUpload;
use Azeemade\BulkUpload\Tests\TestCase; // Assuming base test case exists or I need to create one
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class MetadataUploadTest extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            'Azeemade\BulkUpload\BulkUploadServiceProvider',
            'Maatwebsite\Excel\ExcelServiceProvider',
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('bulk-upload.model_map', [
            'metadata_test_model' => MetadataTestModel::class,
        ]);
        $app['config']->set('bulk-upload.disk', 'local');
    }

    public function test_it_saves_metadata_in_bulk_upload_record()
    {
        Storage::fake('local');
        $header = 'col1,col2';
        $row = 'val1,val2';
        $content = "$header\n$row";

        $file = UploadedFile::fake()->createWithContent('test.csv', $content);

        $metadata = ['batch_source' => 'api', 'priority' => 'high'];

        $this->withoutExceptionHandling(); // Keep this for now
        $response = $this->postJson('api/bulk-upload/import', [
            'model' => 'metadata_test_model',
            'file' => $file,
            'metadata' => $metadata,
        ]);

        if ($response->status() !== 200) {
            dump($response->getContent());
        }
        $response->assertStatus(200);

        $this->assertDatabaseHas('bulk_uploads', [
            'model_class' => MetadataTestModel::class,
        ]);

        $bulkUpload = BulkUpload::first();
        $this->assertArrayHasKey('payload', $bulkUpload->meta);
        $this->assertEquals($metadata, $bulkUpload->meta['payload']);
    }

    // Additional test to ensure backward compatibility (no metadata)
    public function test_it_works_without_metadata()
    {
        Storage::fake('local');
        $header = 'col1,col2';
        $row = 'val1,val2';
        $content = "$header\n$row";

        $file = UploadedFile::fake()->createWithContent('test.csv', $content);

        $response = $this->postJson('api/bulk-upload/import', [
            'model' => 'metadata_test_model',
            'file' => $file,
        ]);

        $response->assertStatus(200);

        $bulkUpload = BulkUpload::first();
        // Should handle empty payload gracefully or just not have it
        $payload = $bulkUpload->meta['payload'] ?? [];
        $this->assertEmpty($payload);
    }
}

class MetadataTestModel implements BulkUploadable
{
    public function getUploadValidationRules(array $row): array
    {
        return [];
    }

    public function processUploadRow(array $row): void
    {
        // Processing logic
    }

    public function getTemplateColumns(): array
    {
        return ['col1', 'col2'];
    }

    public function getTemplateSample(): array
    {
        return [];
    }

    public function getTemplateOptions(): array
    {
        return [];
    }

    public function onUploadComplete($bulkUpload): void
    {
    }

    // The new method
    public function setBulkUploadMetadata(array $metadata): void
    {
        // We could store it in a static property to verify it was called, 
        // but for this unit test of the Controller/Service, simply checking DB is enough 
        // to prove the metadata endpoint works. 
        // To verify Importer calls this, we'd need an integration test running the import.
    }
}
