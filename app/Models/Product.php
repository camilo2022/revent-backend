<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Product extends Model implements Auditable
{
    use Auditing;

    protected $table = 'products';

    protected $guard_name = 'api';

    // marca, categoría y subcategoría quedan solo por compatibilidad: ahora viven en la ficha técnica
    protected $fillable = [
        'code',
        'name',
        'collection_id',
        'trademark_id',
        'category_id',
        'subcategory_id',
        'description',
        'cost',
        'price'
    ];

    protected $auditInclude = [
        'code',
        'name',
        'collection_id',
        'description',
        'cost',
        'price'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

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

    // Colores del producto (nivel color de product_details); las tallas cuelgan de cada color
    public function colors(): MorphMany
    {
        return $this->morphMany(ProductDetail::class, 'model');
    }

    public function technical_sheets(): HasMany
    {
        return $this->hasMany(TechnicalSheet::class);
    }

    // Ficha más reciente (mayor versión)
    public function technical_sheet(): HasOne
    {
        return $this->hasOne(TechnicalSheet::class)->latestOfMany('version');
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
                        ->orWhere('name', 'LIKE', $like)
                        ->orWhere('description', 'LIKE', $like)
                        ->orWhereHas('collection', function (Builder $q) use ($like) {
                            $q->where('name', 'LIKE', $like);
                        });
                });
            }
        });
    }
}
