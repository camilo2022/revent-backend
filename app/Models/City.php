<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class City extends Model implements Auditable
{
    use Auditing;

    protected $table = 'cities';

    protected $fillable = [
        'department_id',
        'name',
        'latitude',
        'longitude',
        'settings'
    ];

    protected $auditInclude = [
        'department_id',
        'name',
        'latitude',
        'longitude',
        'settings'
    ];


    protected $casts = [
        'settings' => 'object'
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
                $q->orWhere('name', 'LIKE', $like);
            }
        });
    }
}
