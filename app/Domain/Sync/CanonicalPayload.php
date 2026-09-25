<?php
namespace App\Domain\Sync;
final class CanonicalPayload
{
    public static function hash(string $type, array $payload): string
    {
        $canonical = ['type' => $type, 'payload' => self::sort($payload)];
        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
    private static function sort(mixed $value): mixed
    {
        if (! is_array($value)) return $value;
        if (array_is_list($value)) return array_map(self::sort(...), $value);
        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) $value[$key] = self::sort($item);
        return $value;
    }
}
