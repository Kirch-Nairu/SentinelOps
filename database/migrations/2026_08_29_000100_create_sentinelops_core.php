<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestampsTz();
        });

        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->timestampsTz();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 96);
            $table->string('name');
            $table->string('status', 32)->default('available');
            $table->unsignedBigInteger('revision')->default(1);
            $table->timestampsTz();
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();
            $table->index(['asset_id', 'ended_at']);
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('severity', 16);
            $table->text('finding');
            $table->string('status', 24)->default('open');
            $table->boolean('created_offline')->default(false);
            $table->unsignedBigInteger('asset_revision_at_creation');
            $table->unsignedBigInteger('revision')->default(1);
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'status']);
            $table->index(['asset_id', 'created_at']);
        });

        Schema::create('evidence_staging', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('storage_key', 512)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 96);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestampTz('expires_at');
            $table->timestampTz('attached_at')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'attached_at', 'expires_at']);
        });

        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('storage_key', 512)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 96);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->index(['organization_id', 'incident_id']);
        });

        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->string('status', 24)->default('open');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('client_operation_id');
            $table->unsignedBigInteger('client_sequence')->default(0);
            $table->string('operation_type', 64);
            $table->char('payload_hash', 64);
            $table->string('status', 24)->default('processing');
            $table->string('rejection_code', 64)->nullable();
            $table->jsonb('result')->nullable();
            $table->timestampTz('executed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['organization_id', 'client_operation_id']);
            $table->index(['organization_id', 'user_id', 'created_at']);
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('event_id')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('client_operation_id')->nullable();
            $table->string('event_type', 96);
            $table->string('subject_type', 96);
            $table->string('subject_id', 128);
            $table->jsonb('data');
            $table->timestampTz('created_at');
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'subject_type', 'subject_id']);
        });

        Schema::create('operational_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 96);
            $table->string('severity', 16)->default('info');
            $table->string('message', 500);
            $table->jsonb('context')->nullable();
            $table->timestampTz('created_at');
            $table->index(['organization_id', 'created_at']);
        });

        DB::statement("ALTER TABLE organization_memberships ADD CONSTRAINT organization_memberships_role_check CHECK (role IN ('administrator','supervisor','technician','security_officer','auditor'))");
        DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_status_check CHECK (status IN ('available','deployed','damaged','maintenance','retired'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_severity_check CHECK (severity IN ('low','medium','high','critical'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_status_check CHECK (status IN ('open','investigating','closed'))");
        DB::statement("ALTER TABLE maintenance_records ADD CONSTRAINT maintenance_status_check CHECK (status IN ('open','completed','cancelled'))");
        DB::statement("ALTER TABLE sync_operations ADD CONSTRAINT sync_operations_status_check CHECK (status IN ('processing','accepted','rejected'))");
        DB::statement('CREATE UNIQUE INDEX asset_assignments_one_active_per_asset ON asset_assignments (asset_id) WHERE ended_at IS NULL');

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION sentinelops_prevent_audit_mutation()
RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'audit_events are append-only';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER audit_events_no_update
BEFORE UPDATE ON audit_events
FOR EACH ROW EXECUTE FUNCTION sentinelops_prevent_audit_mutation();

CREATE TRIGGER audit_events_no_delete
BEFORE DELETE ON audit_events
FOR EACH ROW EXECUTE FUNCTION sentinelops_prevent_audit_mutation();

CREATE OR REPLACE FUNCTION sentinelops_prevent_evidence_mutation()
RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'attached evidence metadata is immutable';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER evidence_no_update
BEFORE UPDATE ON evidence
FOR EACH ROW EXECUTE FUNCTION sentinelops_prevent_evidence_mutation();

CREATE TRIGGER evidence_no_delete
BEFORE DELETE ON evidence
FOR EACH ROW EXECUTE FUNCTION sentinelops_prevent_evidence_mutation();
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_update ON audit_events; DROP TRIGGER IF EXISTS audit_events_no_delete ON audit_events; DROP FUNCTION IF EXISTS sentinelops_prevent_audit_mutation(); DROP TRIGGER IF EXISTS evidence_no_update ON evidence; DROP TRIGGER IF EXISTS evidence_no_delete ON evidence; DROP FUNCTION IF EXISTS sentinelops_prevent_evidence_mutation();');
        Schema::dropIfExists('operational_events');
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('sync_operations');
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('evidence');
        Schema::dropIfExists('evidence_staging');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('organization_memberships');
        Schema::dropIfExists('organizations');
    }
};
