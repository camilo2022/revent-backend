<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

// Cantidad de una talla para una tienda dentro de un color de la orden
class ProductionOrderDetailQuantity extends Model implements Auditable
{
    use Auditing;

    protected $table = 'production_order_detail_quantities';

    protected $guard_name = 'api';

    protected $fillable = [
        'production_order_detail_id',
        'store_id',
        'product_detail_id',
        'quantity'
    ];

    protected $auditInclude = [
        'production_order_detail_id',
        'store_id',
        'product_detail_id',
        'quantity'
    ];

    public function production_order_detail(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderDetail::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Talla del producto (product_details nivel talla)
    public function product_detail(): BelongsTo
    {
        return $this->belongsTo(ProductDetail::class);
    }
}
