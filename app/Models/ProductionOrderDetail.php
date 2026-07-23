<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class ProductionOrderDetail extends Model implements Auditable
{
    use Auditing;

    protected $table = 'production_order_details';

    protected $guard_name = 'api';

    protected $fillable = [
        'production_order_id',
        'product_detail_id',
        'store_id',
        'cost',
        'price',
        'observation'
    ];

    protected $auditInclude = [
        'production_order_id',
        'product_detail_id',
        'store_id',
        'cost',
        'price',
        'observation'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function production_order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product_detail(): BelongsTo
    {
        return $this->belongsTo(ProductDetail::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function production_order_detail_quantities(): HasMany
    {
        return $this->hasMany(ProductionOrderDetailQuantity::class);
    }
}
