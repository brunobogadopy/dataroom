<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('file_versions', function (Blueprint $table) {
            $table->string('extraction_status', 20)->default('pending')->after('extracted_text');
            $table->text('extraction_error')->nullable()->after('extraction_status');
            $table->index(['node_id', 'extraction_status']);
        });
    }

    public function down(): void
    {
        Schema::table('file_versions', function (Blueprint $table) {
            $table->dropIndex(['node_id', 'extraction_status']);
            $table->dropColumn(['extraction_status', 'extraction_error']);
        });
    }
};
