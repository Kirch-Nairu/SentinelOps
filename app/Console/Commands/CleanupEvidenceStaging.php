<?php
namespace App\Console\Commands;
use App\Models\EvidenceStaging;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
class CleanupEvidenceStaging extends Command
{
    protected $signature='sentinelops:cleanup-evidence-staging';
    protected $description='Delete expired, unattached evidence staging bytes and rows.';
    public function handle(): int
    {
        $count=0;
        EvidenceStaging::query()->whereNull('attached_at')->where('expires_at','<',now())->orderBy('id')->chunkById(100,function($rows) use (&$count){
            foreach($rows as $row){ Storage::disk('local')->delete($row->storage_key); $row->delete(); $count++; }
        });
        $this->info("Cleaned {$count} expired staged evidence object(s).");
        return self::SUCCESS;
    }
}
