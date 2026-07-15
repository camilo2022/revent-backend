<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Store extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'stores';

    protected $guard_name = 'api';

    protected $fillable = [
        'code',
        'name',
        'location_id',
        'location_type',
        'address',
        'neighborhood'
    ];

    protected $auditInclude = [
        'code',
        'name',
        'location_id',
        'location_type',
        'address',
        'neighborhood'
    ];

    public function location(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('code', 'LIKE', '%' . $term . '%')
                        ->orWhere('name', 'LIKE', '%' . $term . '%')
                        ->orWhere('document', 'LIKE', '%' . $term . '%')
                        ->orWhere('address', 'LIKE', '%' . $term . '%')
                        ->orWhere('neighborhood', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }
}
