<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Models\Evidence;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\SyncOperation;
use App\Models\User;
use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerifyRecovery extends Command
{
    protected $signature = 'sentinelops:verify-recovery';
    protected $description = 'Verify restored database invariants and attached evidence integrity before declaring recovery complete.';

    public function handle(): int
    {
        DB::select('SELECT 1');

        $counts = [
            'organizations' => Organization::query()->count(),
            'users' => User::query()->count(),
            'assets' => Asset::query()->count(),
            'incidents' => Incident::query()->count(),
            'audit_events' => AuditEvent::query()->count(),
            'sync_operations' => SyncOperation::query()->count(),
            'evidence' => Evidence::query()->count(),
        ];

        foreach ($counts as $name => $count) {
            $this->line("{$name}: {$count}");
        }

        $violations = [
            'duplicate_active_custody' => (int) DB::scalar(<<<'SQL'
SELECT count(*) FROM (
    SELECT asset_id
    FROM asset_assignments
    WHERE ended_at IS NULL
    GROUP BY asset_id
    HAVING count(*) > 1
) duplicates
SQL),
            'orphan_incident_assets' => (int) DB::scalar('SELECT count(*) FROM incidents i LEFT JOIN assets a ON a.id = i.asset_id WHERE a.id IS NULL'),
            'orphan_evidence_incidents' => (int) DB::scalar('SELECT count(*) FROM evidence e LEFT JOIN incidents i ON i.id = e.incident_id WHERE i.id IS NULL'),
        ];

        $auditTriggers = (int) DB::scalar("SELECT count(*) FROM pg_trigger t JOIN pg_class c ON c.oid=t.tgrelid WHERE c.relname='audit_events' AND NOT t.tgisinternal AND t.tgenabled <> 'D'");
        $evidenceTriggers = (int) DB::scalar("SELECT count(*) FROM pg_trigger t JOIN pg_class c ON c.oid=t.tgrelid WHERE c.relname='evidence' AND NOT t.tgisinternal AND t.tgenabled <> 'D'");

        if ($auditTriggers < 2) {
            $violations['audit_immutability_triggers_missing'] = 2 - $auditTriggers;
        }
        if ($evidenceTriggers < 2) {
            $violations['evidence_immutability_triggers_missing'] = 2 - $evidenceTriggers;
        }

        $missing = 0;
        $corrupt = 0;
        $checked = 0;
        Evidence::query()->orderBy('id')->chunkById(100, function ($rows) use (&$missing, &$corrupt, &$checked) {
            foreach ($rows as $evidence) {
                $checked++;
                if (! Storage::disk('local')->exists($evidence->storage_key)) {
                    $missing++;
                    $this->error("MISSING {$evidence->public_id} {$evidence->storage_key}");
                    continue;
                }

                $hash = hash_file('sha256', Storage::disk('local')->path($evidence->storage_key));
                if (! hash_equals($evidence->sha256, $hash)) {
                    $corrupt++;
                    $this->error("HASH_MISMATCH {$evidence->public_id} {$evidence->storage_key}");
                }
            }
        });

        $this->line("Checked evidence: {$checked}; missing: {$missing}; corrupt: {$corrupt}");

        $integrityFailures = array_filter($violations, static fn (int $count): bool => $count > 0);
        foreach ($integrityFailures as $name => $count) {
            $this->error("INVARIANT_FAILURE {$name}={$count}");
        }

        if ($missing || $corrupt || $integrityFailures) {
            $this->error('RECOVERY INCOMPLETE');
            return self::FAILURE;
        }

        $this->info('RECOVERY VERIFIED');
        return self::SUCCESS;
    }
}
