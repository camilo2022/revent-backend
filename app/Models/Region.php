<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Region extends Model implements Auditable
{
    use Auditing;

    protected $table = 'regions';

    protected $fillable = [
        'continent_id',
        'name',
        'translations',
        'settings'
    ];

    protected $auditInclude = [
        'continent_id',
        'name',
        'translations',
        'settings'
    ];

    protected $casts = [
        'translations' => 'object',
        'settings' => 'object'
    ];

    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
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
