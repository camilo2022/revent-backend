<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

/**
 * Dos niveles en una sola tabla:
 * color  -> model = Product,       assignable = Color
 * talla  -> model = ProductDetail, assignable = Size (el model es el color)
 */
class ProductDetail extends Model implements Auditable
{
    use Auditing;

    protected $table = 'product_details';

    protected $guard_name = 'api';

    protected $fillable = [
        'uuid',
        'model_id',
        'model_type',
        'assignable_id',
        'assignable_type',
        'description'
    ];

    protected $auditInclude = [
        'uuid',
        'model_id',
        'model_type',
        'assignable_id',
        'assignable_type',
        'description'
    ];

    // Tallas de un color
    public function sizes(): MorphMany
    {
        return $this->morphMany(ProductDetail::class, 'model');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function photo(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    // Color: referencia-color (RB265001-99). Talla: referencia-talla-color (RB265001-35-99).
    // Usa las relaciones ya cargadas; si no están, hace consultas (evitarlo en listados).
    protected function code(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->model_type === self::class) {
                    return implode('-', [
                        $this->model?->model?->code ?? 'NA',
                        $this->assignable?->settings?->code ?? '00',
                        $this->model?->assignable?->settings?->code ?? '00',
                    ]);
                }

                return implode('-', [
                    $this->model?->code ?? 'NA',
                    $this->assignable?->settings?->code ?? '00',
                ]);
            },
        );
    }
}
