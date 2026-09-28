<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->string('email');
            $table->string('role', 20)->default('member');
            $table->string('token_hash', 64)->unique();
            $table->uuid('invited_by');
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'email']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->string('visibility', 20)->default('workspace')->after('slug');
            $table->index(['workspace_id', 'visibility']);
        });

        Schema::create('node_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('node_id');
            $table->uuid('user_id');
            $table->string('permission', 20);
            $table->timestamps();

            $table->unique(['node_id', 'user_id']);
            $table->index(['workspace_id', 'user_id']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('node_id')->references('id')->on('nodes')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_permissions');
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'visibility']);
            $table->dropColumn('visibility');
        });
        Schema::dropIfExists('workspace_invitations');
    }
};
