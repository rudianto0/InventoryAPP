<?php

namespace App\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Base migration that provides a single source of truth for the audit &
 * soft-delete columns that must exist on every application table.
 *
 * Extend this class instead of Illuminate\Database\Migrations\Migration,
 * then call $this->auditColumns($table) inside your Schema::create closure.
 */
abstract class AuditableMigration extends Migration
{
    /**
     * Add the common audit & soft-delete columns to the given table.
     *
     * Columns added (in order):
     *  - id               : primary key
     *  - created_user_id  : who created the record (nullable)
     *  - created_at       : creation time
     *  - updated_user_id  : who last modified the record (nullable)
     *  - updated_at       : last modification time
     *  - deleted_user_id  : who deleted the record (nullable)
     *  - deleted_at       : deletion time (soft delete)
     */
    protected function auditColumns(Blueprint $table): void
    {
        $table->id();

        $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('created_at')->useCurrent();

        $table->foreignId('updated_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

        $table->foreignId('deleted_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->softDeletes();
    }
}
