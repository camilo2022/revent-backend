<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'modules';

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'icon'
    ];

    protected $auditInclude = [
        'name',
        'icon'
    ];

    protected $auditEvents = [
        'created',
        'updated',
    ];

    public function submodules(): HasMany
    {
        return $this->hasMany(Submodule::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                        ->orWhere('icon', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }
}
