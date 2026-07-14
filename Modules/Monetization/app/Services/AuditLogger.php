<?php

namespace Modules\Monetization\app\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Monetization\app\Models\MonetizationAuditLog;

/**
 * One-line audit writes for every money-shaping mutation. Failures are
 * swallowed into the log channel — an audit hiccup must not roll back
 * the (already transactional) business write it documents; the DB
 * transaction wrapping most callers gives atomicity where it matters.
 */
class AuditLogger
{
    public static function log(string $action, ?Model $subject = null, ?array $changes = null): void
    {
        try {
            $user = auth()->user();

            MonetizationAuditLog::create([
                'actor_id' => $user?->id,
                'actor_name' => $user?->username ?? $user?->email ?? 'system',
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'changes' => $changes,
                'ip' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Monetization audit log write failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Convenience: diff two attribute arrays and log only changed keys.
     */
    public static function logDiff(string $action, ?Model $subject, array $before, array $after): void
    {
        $changedBefore = [];
        $changedAfter = [];

        foreach ($after as $key => $value) {
            $old = $before[$key] ?? null;
            if ((string) $old !== (string) $value) {
                $changedBefore[$key] = $old;
                $changedAfter[$key] = $value;
            }
        }

        if ($changedAfter === []) {
            return;
        }

        static::log($action, $subject, ['before' => $changedBefore, 'after' => $changedAfter]);
    }
}
