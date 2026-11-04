<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

/**
 * Colección: subítem del ítem Colecciones. En settings guarda el año (2 dígitos), el código (1 dígito)
 * y el último consecutivo de producto generado.
 */
class Collection extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    public const ITEM_ID = 17;

    protected $table = 'subitems';

    protected $guard_name = 'api';

    protected $fillable = [
        'item_id',
        'name',
        'description',
        'settings'
    ];

    protected $auditInclude = [
        'item_id',
        'name',
        'description',
        'settings'
    ];

    protected $casts = [
        'settings' => 'object'
    ];

    protected static function booted()
    {
        static::addGlobalScope('item_id', function (Builder $builder) {
            $builder->where('item_id', self::ITEM_ID);
        });

        static::creating(function ($model) {
            $model->item_id = self::ITEM_ID;
        });

        static::updating(function ($model) {
            $model->item_id = self::ITEM_ID;
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'collection_id');
    }

    protected function year(): Attribute
    {
        return Attribute::make(get: fn () => $this->settings?->year);
    }

    protected function code(): Attribute
    {
        return Attribute::make(get: fn () => $this->settings?->code);
    }

    protected function lastNumber(): Attribute
    {
        return Attribute::make(get: fn () => (int) ($this->settings?->last_number ?? 0));
    }

    // Prefijo de los productos de la colección: marca + año + colección (RB265)
    protected function productPrefix(): Attribute
    {
        return Attribute::make(get: fn () => config('products.prefix') . $this->year . $this->code);
    }

    // Código del próximo producto (solo vista previa: el real se asigna al guardar)
    protected function nextCode(): Attribute
    {
        return Attribute::make(get: fn () => $this->product_prefix . str_pad($this->last_number + 1, 3, '0', STR_PAD_LEFT));
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
                        ->orWhere('settings->year', 'LIKE', $like)
                        ->orWhere('settings->code', 'LIKE', $like);
                });
            }
        });
    }
}
