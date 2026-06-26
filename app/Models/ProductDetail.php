<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class ProductDetail extends Model implements Auditable
{
    use Auditing;

    protected $table = 'product_details';

    protected $guard_name = 'api';

    protected $fillable = [
        'uuid',
        'product_id',
        'color_id',
        'size_id',
        'description'
    ];

    protected $auditInclude = [
        'uuid',
        'product_id',
        'color_id',
        'size_id',
        'description'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
