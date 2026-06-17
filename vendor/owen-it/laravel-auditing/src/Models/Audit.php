<?php

namespace OwenIt\Auditing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property string $tags
 * @property string $event
 * @property array<string,mixed> $new_values
 * @property array<string,mixed> $old_values
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property mixed $user
 * @property mixed $auditable.
 * @property string|null $auditable_type
 * @property string|int|null $auditable_id
 */
class Audit extends Model implements \OwenIt\Auditing\Contracts\Audit
{
    use \OwenIt\Auditing\Audit;

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * Is globally auditing disabled?
     *
     * @var bool
     */
    public static $auditingGloballyDisabled = false;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        // Note: Please do not add 'auditable_id' in here, as it will break non-integer PK models
    ];

    public function getSerializedDate(\DateTimeInterface $date): string
    {
        return $this->serializeDate($date);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhereHas('user.employee.person', function (Builder $auditQuery) use ($term) {
                        $auditQuery->where('names', 'LIKE', "%{$term}%")
                            ->orWhere('last_names', 'LIKE', "%{$term}%");
                    });
                });
            }
        });
    }
}
