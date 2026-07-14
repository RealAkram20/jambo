<?php

namespace Modules\Monetization\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $actor_id
 * @property ?string $actor_name
 * @property string $action
 * @property ?array $changes
 */
class MonetizationAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'monetization_audit_logs';

    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
