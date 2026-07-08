<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Country extends Model implements Auditable
{
    use Auditing;

    protected $table = 'countries';

    protected $fillable = [
        'region_id',
        'name',
        'iso3',
        'iso2',
        'numeric_code',
        'phone_code',
        'currency',
        'currency_name',
        'currency_symbol',
        'tld',
        'native',
        'nationality',
        'latitude',
        'longitude',
        'emoji',
        'emojiU',
        'translations',
        'settings'
    ];

    protected $auditInclude = [
        'region_id',
        'name',
        'iso3',
        'iso2',
        'numeric_code',
        'phone_code',
        'currency',
        'currency_name',
        'currency_symbol',
        'tld',
        'native',
        'nationality',
        'latitude',
        'longitude',
        'emoji',
        'emojiU',
        'translations',
        'settings'
    ];

    protected $casts = [
        'translations' => 'object',
        'settings' => 'object'
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
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
                    $sq->orWhere('name', 'LIKE', $like)
                        ->orWhere('iso3', 'LIKE', $like)
                        ->orWhere('iso2', 'LIKE', $like);
                });
            }
        });
    }
}
