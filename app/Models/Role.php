<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Role extends SpatieRole implements Auditable
{
    use Auditing;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'guard_name',
        'title',
        'description',
    ];

    protected $auditInclude = [
        'name',
        'guard_name',
        'title',
        'description',
    ];

    protected $hidden = [
        'guard_name'
    ];

    protected static function  booted()
    {
        static::creating(function ($model) {
            $model->guard_name = 'api';
        });

        static::updating(function ($model) {
            $model->guard_name = 'api';
        });
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
                        ->orWhere('title', 'LIKE', '%' . $term . '%')
                        ->orWhere('description', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }
}
