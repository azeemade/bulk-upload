<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulk_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique();
            $table->string('model_class');
            $table->string('file_path');
            $table->string('error_file_path')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->string('status')->default('pending'); // pending, processing, completed, partially_failed, failed
            $table->nullableMorphs('user'); // user_id, user_type
            $table->string('tenant_id')->nullable()->index();
            $table->json('meta')->nullable(); // Extended info
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_uploads');
    }
};
