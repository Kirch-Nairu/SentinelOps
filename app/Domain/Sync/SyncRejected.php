<?php
namespace App\Domain\Sync;
use RuntimeException;
final class SyncRejected extends RuntimeException
{
    public function __construct(public readonly string $reasonCode, public readonly array $reconciliation = [], string $message = '')
    {
        parent::__construct($message ?: $reasonCode);
    }
}
