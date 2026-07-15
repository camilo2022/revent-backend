<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Product extends Model implements Auditable
{
    use Auditing;

    protected $table = 'products';

    protected $guard_name = 'api';

    protected $fillable = [
        'trademark_id',
        'code',
        'category_id',
        'subcategory_id',
        'description'
    ];

    protected $auditInclude = [
        'trademark_id',
        'code',
        'category_id',
        'subcategory_id',
        'description'
    ];

    public function trademark(): BelongsTo
    {
        return $this->belongsTo(Trademark::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function colors(): HasMany
    {
        return $this->hasMany(ProductDetail::class);
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

                    $sq->where('code', 'LIKE', $like)
                        ->orWhereHas('trademark', function (Builder $q) use ($like) {
                            $q->where('name', 'LIKE', $like)
                            ->orWhere('description', 'LIKE', $like)
                            ->orWhere('settings->code', 'LIKE', $like);
                        })
                        ->orWhereHas('category', function (Builder $q) use ($like) {
                            $q->where('name', 'LIKE', $like)
                            ->orWhere('description', 'LIKE', $like)
                            ->orWhere('settings->code', 'LIKE', $like);
                        })
                        ->orWhereHas('subcategory', function (Builder $q) use ($like) {
                            $q->where('name', 'LIKE', $like)
                            ->orWhere('description', 'LIKE', $like)
                            ->orWhere('settings->code', 'LIKE', $like);
                        });
                });
            }
        });
    }
}
