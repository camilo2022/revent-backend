<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Collection extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'collections';

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'description',
        'year',
        'code',
        'last_number'
    ];

    // El consecutivo se mueve con cada producto: no se audita
    protected $auditInclude = [
        'name',
        'description',
        'year',
        'code'
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Prefijo de los productos de la colección: marca + año + colección (RB265)
    protected function productPrefix(): Attribute
    {
        return Attribute::make(
            get: fn () => config('products.prefix') . $this->year . $this->code,
        );
    }

    // Código del próximo producto (solo vista previa: el real se asigna al guardar)
    protected function nextCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->product_prefix . str_pad($this->last_number + 1, 3, '0', STR_PAD_LEFT),
        );
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

                    $sq->where('name', 'LIKE', $like)
                        ->orWhere('description', 'LIKE', $like)
                        ->orWhere('year', 'LIKE', $like)
                        ->orWhere('code', 'LIKE', $like);
                });
            }
        });
    }
}
