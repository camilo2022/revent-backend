<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Continent extends Model implements Auditable
{
    use Auditing;

    protected $table = 'continents';

    protected $fillable = [
        'name',
        'translations',
        'settings'
    ];

    protected $auditInclude = [
        'name',
        'translations',
        'settings'
    ];

    protected $casts = [
        'translations' => 'object',
        'settings' => 'object'
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

                $q->where(function (Builder $sq) use ($like) {
                    $sq->orWhere('name', 'LIKE', $like);
                });
            }
        });
    }
}
